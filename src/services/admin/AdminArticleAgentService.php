<?php

namespace App\Services\Admin;

use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use Ramsey\Uuid\Uuid;
use App\Helpers\General;
use App\Models\ArticleAgent;
use App\Lib\GeminiClient;
use App\Lib\TavilyClient;
use App\Lib\OpenAIClient;

class AdminArticleAgentService
{
  private string $modelGeminiThink      = 'gemini-2.5-pro';
  private string $modelGeminiSummary    = 'gemini-2.5-flash-lite';
  private string $modelOpenaiBrainstorm = 'gpt-6-astra';
  private string $modelOpenaiSummary    = 'gpt-5.4-mini';
  private string $modelOpenaiWriting    = 'gpt-6-astra';

  public function __construct(
    private General $helper,
    private GeminiClient $gemini,
    private TavilyClient $tavily,
    private OpenAIClient $openai
  ) {}

  /**
   * Langkah 3 Skenario A: Input Topik (AI Brainstorming)
   */
  public function step1_inputTopic(array $input)
  {
    $topic     = trim($input['topic'] ?? '');
    $topicMode = $input['topic_mode'] ?? 'manual';
    $strategy  = $input['strategy'] ?? 'free';
    $site      = $input['site'] ?? 'all';

    $isAutoMode = ($topicMode === 'auto');
    $isAutoFree = $isAutoMode && $strategy === 'free';

    //? Get reference data
    $topArticles    = [];
    $categories     = [];
    $recentArticles = [];
    if ($isAutoFree) {
      //? Auto + Free: gunakan kategori site sebagai referensi dan recent articles sebagai negative context
      $categories     = $this->helper->getCategoriesBySite($site);
      $recentArticles = $this->helper->getRecentArticlesBySite($site, 75);
    } elseif ($isAutoMode || $strategy !== 'free') {
      //? Auto + strategy lain / Manual + non-free: gunakan top articles
      $topArticles = $this->helper->getTopArticles($site, 50);
    }

    //* 1. Susun Base Prompt & Inject Referensi/Strategi
    if ($isAutoMode) {
      //! AUTO MODE: Tanpa topik dari user, murni analisis data website
      $prompt = "Kamu adalah seorang ahli strategi konten SEO. Berdasarkan analisis data website, berikan 10 rekomendasi ide topik dan artikel baru yang berpotensi memiliki trafik tinggi.\n\n";

      if ($isAutoFree) {
        //! AUTO + FREE: Gunakan kategori site sebagai referensi
        if (!empty($categories)) {
          $prompt .= "DAFTAR KATEGORI ARTIKEL DI SITE INI:\n";
          foreach ($categories as $index => $category) {
            $num     = $index + 1;
            $desc    = $category['description'] !== '-' ? " — {$category['description']}" : '';
            $prompt .= "{$num}. {$category['title']}{$desc}\n";
          }
          $prompt .= "\nBerdasarkan kategori-kategori di atas, buatlah 10 rekomendasi ide artikel yang relevan dengan fokus dan niche site ini. Pastikan ide-ide tersebut tren, menarik, dan berpotensi meraih trafik tinggi. Sebarkan ide secara merata di berbagai kategori yang tersedia.\n\n";
        } else {
          $prompt .= "Buatlah 10 rekomendasi ide artikel umum yang populer, tren, dan berpotensi meraih trafik tinggi.\n\n";
        }

        //! Anti-duplikasi (Negative Context) dari artikel eksisting
        if (!empty($recentArticles)) {
          $prompt .= "DAFTAR ARTIKEL YANG SUDAH DITULIS DI SITUS INI (DILARANG KERAS MEREKOMENDASIKAN ULANG):\n";
          foreach ($recentArticles as $index => $article) {
            $num     = $index + 1;
            $prompt .= "{$num}. {$article['title']} | Keyword: {$article['keyword']}\n";
          }
          $prompt .= "\nINSTRUKSI PENCEGAHAN DUPLIKASI (SANGAT PENTING):\n";
          $prompt .= "DILARANG KERAS membuat ide topik yang mengangkat tema, judul, atau target kata kunci yang serupa atau telah dibahas dalam daftar di atas untuk mencegah kanibalisasi konten (keyword cannibalization). Rekomendasikan topik yang benar-benar SEGAR, BARU, dan BELUM PERNAH DITULIS di situs ini.\n\n";
        }

        //! Dynamic Angles Pool & Waktu Terkini
        $anglesPool = [
          "Solusi Praktis & Actionable How-To (Mengatasi masalah riil audiens langkah demi langkah)",
          "Tren Terkini & Masa Depan / Future Outlook (Prediksi, inovasi, dan perkembangan baru industri)",
          "Mitos vs Fakta / Sudut Pandang Kontrarian (Membongkar kesalahpahaman umum dan perspektif kritis)",
          "Panduan Komprehensif Pemula / Fondasi (Penjelasan mendasar dari nol yang mudah dipahami)",
          "Komparasi Mendalam & Studi Kasus Riil (Perbandingan solusi / bedah kasus nyata yang objektif)"
        ];
        shuffle($anglesPool);
        $selectedAngles = array_slice($anglesPool, 0, 2);

        $monthsIndo = [
          1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
          5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
          9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $currentPeriod = ($monthsIndo[(int)date('n')] ?? date('F')) . ' ' . date('Y');

        $prompt .= "EKSPLORASI SUDUT PANDANG (DYNAMIC ANGLES):\n";
        $prompt .= "Eksplorasi ide dengan fokus utama pada 2 sudut pandang (angle) berikut:\n";
        $prompt .= "1. {$selectedAngles[0]}\n";
        $prompt .= "2. {$selectedAngles[1]}\n";
        $prompt .= "Sesuaikan juga topik dengan relevansi konteks tren waktu saat ini ({$currentPeriod}).\n\n";
      } elseif (!empty($topArticles)) {
        //! AUTO + STRATEGY (gap/cluster/formula): Gunakan top articles
        $prompt .= "REFERENSI 50 ARTIKEL TERPOPULER SAYA:\n";
        foreach ($topArticles as $index => $article) {
          $num = $index + 1;
          $prompt .= "{$num}. {$article['title']} | Keyword: {$article['keyword']}\n";
        }
        $prompt .= "\nINSTRUKSI STRATEGI:\n";

        switch ($strategy) {
          case 'gap': //! "Hindari Topik Serupa (Cari Celah Baru)"
            $prompt .= "Analisis 50 artikel terpopuler di atas secara mendalam. Identifikasi topik, angle, atau perspektif yang BELUM ter-cover sama sekali. Buat 10 ide artikel dengan angle yang SAMA SEKALI BERBEDA dan belum ada di daftar tersebut untuk mencegah keyword cannibalization.\n\n";
            break;
          case 'cluster': //! "Buat Topik Turunan (Pillar & Cluster)"
            $prompt .= "Gunakan 50 artikel terpopuler di atas sebagai 'Pillar'. Buat 10 ide artikel turunan (cluster content) yang bisa mendalami topik-topik dari referensi tersebut dan sangat relevan untuk dipasang internal link menuju daftar referensi.\n\n";
            break;
          case 'formula': //! "Tiru Formula Judul Teratas"
            $prompt .= "Analisis pola psikologis (misal: listicle, FOMO, how-to) dari 50 artikel terpopuler di atas. Terapkan formula penulisan judul dan angle sukses tersebut ke 10 ide artikel baru yang berpotensi meraih trafik tinggi.\n\n";
            break;
        }
      } else {
        $prompt .= "Buatlah 10 rekomendasi ide artikel umum yang populer, tren, dan berpotensi meraih trafik tinggi.\n\n";
      }
    } else {
      //! MANUAL MODE: Berdasarkan topik dari user
      $prompt = "Kamu adalah seorang ahli strategi konten SEO. Dengan topik utama '{$topic}', buatlah 10 ide artikel unik dan berpotensi trafik tinggi.\n\n";

      if (!empty($topArticles)) {
        $prompt .= "REFERENSI 50 ARTIKEL TERPOPULER SAYA:\n";
        foreach ($topArticles as $index => $article) {
          $num = $index + 1;
          $prompt .= "{$num}. {$article['title']} | Keyword: {$article['keyword']}\n";
        }
        $prompt .= "\nINSTRUKSI STRATEGI:\n";

        switch ($strategy) {
          case 'gap': //! "Hindari Topik Serupa (Cari Celah Baru)"
            $prompt .= "Bandingkan topik '{$topic}' dengan daftar referensi di atas. Buat 10 ide artikel dengan angle yang SAMA SEKALI BERBEDA dan belum ter-cover di daftar tersebut untuk mencegah keyword cannibalization.\n\n";
            break;
          case 'cluster': //! "Buat Topik Turunan (Pillar & Cluster)"
            $prompt .= "Gunakan daftar referensi di atas sebagai 'Pillar'. Buat 10 ide artikel turunan (cluster content) dari topik '{$topic}' yang bisa mendalami referensi tersebut dan sangat relevan untuk dipasang internal link menuju daftar referensi.\n\n";
            break;
          case 'formula': //! "Tiru Formula Judul Teratas"
            $prompt .= "Analisis pola psikologis (misal: listicle, FOMO, how-to) dari daftar referensi di atas. Terapkan formula penulisan judul dan angle sukses tersebut ke 10 ide artikel baru untuk topik '{$topic}'.\n\n";
            break;
        }
      }
    }

    //* 3. Format Output JSON Strict
    $prompt .= "Untuk setiap ide, berikan:\n" .
               "1. Judul yang memancing klik (CTR tinggi)\n" .
               "2. Kata kunci utama (Target Keyword)\n" .
               "3. Ringkasan singkat isi konten (Maksimal 2 kalimat).. Langsung ke inti bahasan. DILARANG KERAS menggunakan awalan seperti 'Artikel ini membahas...', 'Konten ini menjelaskan...', 'Panduan ini...', dsb.\n\n" .
               "KEMBALIKAN HASILNYA HANYA SEBAGAI ARRAY JSON objek dengan kunci persis seperti ini: 'title', 'keyword', 'summary'. Jangan sertakan format markdown, backticks (```json), atau teks pengantar apa pun di luar JSON.";

    //* 4. Hit LLM (OpenAI / Gemini)
    $rawResponse = $this->openai->generateContent("", $prompt, $this->modelOpenaiBrainstorm, []);

    if (!$rawResponse) {
      throw new Exception("Failed to generate brainstorming options from Models.");
    }

    //* 5. Bersihkan dan Decode JSON
    $cleanJson    = trim(str_replace(['```json', '```'], '', $rawResponse));
    $optionsArray = json_decode($cleanJson, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new Exception("Output bukan JSON valid: " . json_last_error_msg() . " | Raw: " . substr($rawResponse, 0, 100));
    }

    //* 6. Generate UUID & Simpan DB
    $uuid = Uuid::uuid4()->toString();
    ArticleAgent::create([
      'id'                    => $uuid,
      'user_input_topic'      => $isAutoMode ? 'Auto (' . ucfirst($strategy) . ')' : $topic,
      'brainstorming_options' => $optionsArray,
    ]);

    //* 7. Return Hasil
    return [
      'uuid'    => $uuid,
      'options' => $optionsArray
    ];
  }

  /**
   * Langkah 3 Skenario A (Select Option) & Skenario B (Manual Input)
   */
  public function step1_inputSelection(array $input)
  {
    // $input expected: 
    // - uuid (optional)
    // - selection (array: {title, keyword, intent}) - if picking from brainstorming
    // - manual_input (boolean) - flag if this is direct manual input

    $uuid          = $input['uuid'] ?? null;
    $selectionData = $input['selection'] ?? null;
    if (!$selectionData) {
      throw new Exception("Selection data is required.");
    }

    if ($uuid) {
      // --- KONDISI: Session Sudah Ada ---
      $session = ArticleAgent::find($uuid);
      if (!$session) {
        throw new Exception("Session not found.");
      }

      $session->update([
        'selected_option_data' => $selectionData
      ]);
    } 
    else {
      // --- KONDISI: Session Baru ---
      $uuid = Uuid::uuid4()->toString();
      ArticleAgent::create([
        'id'                   => $uuid,
        'selected_option_data' => $selectionData,
      ]);
    }

    $researchManual = $input['research_manual'] ?? false;
    $researchResult = $this->step2_doResearch(['uuid' => $uuid, 'research_manual' => $researchManual]);
    return array_merge(['uuid' => $uuid], $researchResult);
  }

  /**
   * Langkah 4: Research (Deep Dive)
   */
  public function step2_doResearch(array $input)
  {
    $session = ArticleAgent::find($input['uuid']);
    if (!$session) {
      throw new Exception("Session not found.");
    }

    $selectedData = $session->selected_option_data;
    if (!$selectedData || !isset($selectedData['keyword'])) {
      throw new Exception("No keyword selected for research.");
    }

    //* JIKA MANUAL, SKIP TAVILY & OPENAI
    $researchManual = $input['research_manual'] ?? false;
    if ($researchManual) {
      return [
        'uuid'                => $input['uuid'],
        'research_raw_tavily' => null,
        'research_summary'    => null,
        'cleaned'             => null
      ];
    }

    //* 1. Tavily Search
    $query        = ($selectedData['title'] ?? "") . ". Keyword: " . $selectedData['keyword'];
    $tavilyResult = $this->tavily->search($query, 7, ['youtube.com', 'tiktok.com']);
    if (!$tavilyResult) {
      throw new Exception("Failed to retrieve research data.");
    }

    //* 2. Extract text for Research
    $textForResearch = $this->helper->clean_tavily_context($tavilyResult['results'] ?? [], 12000);

    //! Prompt
    $prompt = "Kamu adalah seorang analis riset profesional. Analisis data riset mentah berikut yang berisi cuplikan dan konten dari hasil pencarian.\n" .
              "Tugas:\n" .
              "1. Saring informasi yang tidak relevan atau duplikat.\n" .
              "2. Sintesiskan poin-poin penting menjadi ringkasan yang komprehensif.\n" .
              "3. Sertakan fakta, statistik, dan wawasan yang relevan.\n" .
              "4. Cantumkan URL sumber untuk klaim utama jika memungkinkan.\n" .
              "\nData Mentah:\n" . $textForResearch . "\n\n" .
              "INSTRUKSI OUTPUT (PENTING):\n" .
              "- Hasilkan ringkasan dalam format Markdown yang rapi.\n" .
              "- JANGAN berikan kalimat pembuka, kata pengantar, atau basa-basi (seperti 'Berikut adalah ringkasan...').\n" .
              "- JANGAN ada teks apapun sebelum Judul Utama.\n" .
              "- Karakter pertama dari responmu HARUS berupa tanda pagar (#) untuk Judul Utama.";

    $summary = $this->openai->generateContent("", $prompt, $this->modelOpenaiSummary, [
      'temperature' => 0.7
    ]);

    //* 3. Update DB
    $session->update([
      'research_raw_tavily' => json_encode($tavilyResult, JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE),
      'research_summary'    => $summary
    ]);

    return [
      'uuid'                => $input['uuid'],
      'research_raw_tavily' => $tavilyResult,
      'research_summary'    => $summary,
      'cleaned'             => $textForResearch
    ];
  }

  /**
   * Langkah 5: Writing (Generation & Review)
   */
  public function step3_doWrite(array $input)
  {
    $uuid    = $input['uuid'] ?? null;
    $summary = $input['research_summary'] ?? null;
    $query   = ArticleAgent::find($input['uuid']);
    if (!$query) {
      throw new Exception("Query not found.");
    }

    if (!empty($summary)) {
      $query->update(['research_summary' => $summary]);
      $query->refresh();
    }
    if (!$query->research_summary) {
      throw new Exception("Research summary missing. Cannot generate article.");
    }

    $selectedData = $query->selected_option_data;

    $systemPrompt = <<<PROMPT
      Kamu adalah penulis artikel profesional berbahasa Indonesia yang sangat baik dalam menulis seperti manusia.
      Tugas utamamu adalah menghasilkan artikel yang:
      - enak dibaca,
      - informatif,
      - natural,
      - terasa ditulis oleh manusia,
      - memiliki alur pemikiran yang jelas,
      - menjawab kebutuhan pembaca,
      - dan tetap ramah mesin pencari.

      PRINSIP UTAMA:
      - Kualitas tulisan dan kegunaan bagi pembaca selalu lebih penting daripada memasukkan sebanyak mungkin aturan SEO.
      - Jangan menulis dengan gaya "artikel SEO generik".
      - Jangan membuat setiap paragraf terdengar seperti template.
      - Jangan memaksakan keyword, heading, list, tabel, atau kalimat tertentu jika memang tidak dibutuhkan.
      - Tulis seperti seorang penulis yang benar-benar memahami topik dan sedang menjelaskannya kepada pembaca nyata.
      - Gunakan hasil riset sebagai sumber fakta dan bahan berpikir, bukan sebagai teks yang harus diparafrasekan.
      - Jangan menyalin kalimat, susunan paragraf, urutan pembahasan, atau gaya bahasa dari sumber.
      - Sintesis informasi dari riset lalu bangun artikel baru dengan struktur dan alur yang natural.

      GAYA PENULISAN:
      - Tulis dalam bahasa Indonesia yang natural dan modern.
      - Gunakan pilihan kata yang umum digunakan oleh penutur bahasa Indonesia.
      - Utamakan kejelasan daripada kata-kata yang terdengar canggih.
      - Variasikan panjang kalimat secara alami.
      - Variasikan panjang paragraf secara alami.
      - Gunakan kalimat pendek ketika memang cocok untuk penekanan.
      - Gunakan kalimat lebih panjang ketika penjelasan membutuhkan konteks.
      - Pisahkan paragraf berdasarkan perubahan ide, bukan berdasarkan jumlah kalimat.
      - Hindari pengulangan ide yang sama dengan kata-kata berbeda.
      - Hindari pembukaan yang terlalu panjang atau bertele-tele.
      - Jangan mengawali artikel dengan kalimat generik seperti "Di era digital...", "Saat ini...", "Bagi banyak orang...", kecuali memang benar-benar relevan dengan topik.
      - Jangan mengulang judul atau keyword secara tidak perlu.
      - Jangan membuat semua bagian terdengar terlalu formal.
      - Jangan membuat semua kalimat memiliki pola yang sama.
      - Jangan menggunakan gaya bahasa seperti brosur, press release, atau template SEO.
      - Gunakan gaya menjelaskan sesuatu kepada manusia nyata.

      HINDARI:
      - filler,
      - basa-basi,
      - pengulangan,
      - kalimat yang hanya dibuat untuk memperpanjang artikel,
      - hiperbola,
      - klaim yang terlalu besar,
      - bahasa promosi generik,
      - jargon yang tidak diperlukan,
      - frasa template yang terasa dibuat oleh AI.

      KEASLIAN DAN RISET:
      - Gunakan hasil riset sebagai basis fakta.
      - Jangan sekadar melakukan parafrase dari research summary.
      - Jangan mengikuti urutan sumber secara mekanis.
      - Gabungkan informasi yang relevan menjadi artikel baru yang memiliki alur logis.
      - Jangan menyalin kalimat dari sumber.
      - Jangan membuat fakta, statistik, angka, tanggal, nama, harga, penelitian, manfaat, spesifikasi, atau klaim yang tidak didukung oleh data riset.
      - Jika sebuah informasi tidak tersedia atau tidak cukup jelas dalam riset, jangan mengarang detail untuk melengkapinya.
      - Jika terdapat informasi yang tampak bertentangan, jangan membuat kesimpulan baru tanpa dasar. Gunakan informasi yang paling jelas didukung oleh sumber.

      SEARCH INTENT:
      Sebelum menulis, pahami maksud pencarian di balik focus keyword dan judul.
      Artikel harus menjawab kebutuhan utama pembaca secepat mungkin.
      Jika topiknya informasional:
      - berikan penjelasan yang jelas,
      - gunakan contoh jika tersedia dalam riset,
      - fokus pada informasi yang benar-benar dibutuhkan pembaca.
      Jika topiknya berupa panduan:
      - berikan langkah yang praktis dan masuk akal,
      - gunakan urutan yang mudah diikuti.
      Jika topiknya berupa perbandingan:
      - jelaskan perbedaan secara jelas,
      - gunakan tabel bila benar-benar membantu.
      Jika topiknya membutuhkan pertimbangan:
      - jelaskan kelebihan,
      - kekurangan,
      - batasan,
      - dan konteks penggunaan bila didukung oleh riset.
      Jangan membuat struktur artikel hanya karena "SEO membutuhkan banyak heading".
      Setiap bagian harus memiliki alasan yang jelas untuk berada di artikel.

      SEO:
      Focus keyword: {$selectedData['keyword']}
      Judul SEO: {$selectedData['title']}

      Gunakan focus keyword secara alami.
      Gunakan variasi keyword, sinonim, istilah terkait, dan frasa semantik jika memang membantu konteks.
      Jangan menentukan jumlah kemunculan keyword secara kaku.
      Jangan memasukkan keyword hanya untuk memenuhi kepadatan tertentu.
      Jangan menggunakan keyword secara paksa di setiap heading.
      Jangan mengulang keyword pada kalimat yang terasa tidak natural.
      Keyword tidak perlu dibuat bold.

      Prioritaskan:
      1. search intent,
      2. kualitas informasi,
      3. keterbacaan,
      4. struktur konten,
      5. SEO.

      Jangan menggunakan kalimat seperti:
      - "Artikel ini akan membahas..."
      - "Pada artikel ini kita akan..."
      - "Berikut adalah pembahasan..."
      kecuali memang diperlukan secara natural.

      STRUKTUR ARTIKEL:
      Mulai artikel dengan judul yang diberikan.
      Jangan gunakan tag <h1> karena judul halaman ditangani oleh sistem website.
      Gunakan:
      - <h2> untuk bagian utama.
      - <h3> hanya ketika sub-bagian memang diperlukan.
      Tidak semua bagian harus memiliki jumlah paragraf yang sama.
      Tidak semua subheading harus memiliki dua atau lebih paragraf.
      Gunakan struktur yang terasa seperti artikel sungguhan, bukan template.
      Gunakan list hanya ketika informasi memang lebih mudah dipahami dalam bentuk list.
      Gunakan tabel hanya ketika informasi memang cocok dibandingkan atau disajikan dalam format tabular.
      Jangan menambahkan bagian hanya untuk memenuhi struktur SEO.
      Jangan menambahkan kesimpulan formal hanya karena kebiasaan artikel SEO.
      Artikel boleh berakhir setelah poin penting terakhir selesai dibahas.

      PANJANG ARTIKEL:
      Tulis sepanjang yang diperlukan untuk menjawab search intent dengan lengkap.
      Sebagai panduan umum, artikel biasanya berada di kisaran 600-1000 kata.
      Jangan menambahkan kalimat, paragraf, atau subheading hanya untuk mengejar jumlah kata.
      Kelengkapan dan kualitas lebih penting daripada jumlah kata.

      HTML:
      Output content harus berupa HTML mentah.
      Gunakan:
      - <h2>
      - <h3>
      - <p>
      - <strong>
      - <em>
      - <ul>
      - <ol>
      - <li>
      - <table>
      - <thead>
      - <tbody>
      - <tr>
      - <th>
      - <td>
      - <blockquote>
      Gunakan tag tersebut hanya ketika memang relevan.
      Jangan gunakan:
      - <h1>
      - <html>
      - <head>
      - <body>
      - markdown
      - markdown code block
      - ```html
      - ```
      HTML harus valid dan siap disimpan langsung sebagai body artikel.

      LINK EKSTERNAL:
      Jika research summary menyediakan sumber atau URL yang relevan, sertakan maksimal satu hyperlink eksternal yang benar-benar membantu pembaca.
      Gunakan format: <a href="URL" target="_blank" rel="noopener noreferrer">teks link</a>
      Jangan membuat URL baru atau menebak URL.
      Gunakan hanya URL yang memang tersedia dalam hasil riset.
      Jika tidak ada sumber eksternal yang relevan, tidak perlu memaksakan hyperlink.

      PROMPTs GAMBAR
      Buat:
      1. satu prompt gambar cover artikel,
      2. satu prompt gambar untuk setiap <h2> utama.
      Format wajib: [Gambar: Deskripsi visual detail...]

      PROMPTs GAMBAR COVER:
      Letakkan tepat sebelum paragraf pertama artikel.
      Prompt cover harus menggambarkan topik utama artikel secara visual dan relevan.
      Jangan hanya mengubah judul artikel menjadi deskripsi gambar.

      PROMPTs GAMBAR SUBHEADING:
      Letakkan tepat setelah setiap <h2> yang bersangkutan.
      Setiap prompt harus menggambarkan isi atau konteks dari bagian tersebut.
      Hindari visual generik yang tidak berhubungan langsung dengan isi subheading.
      Prompt harus cukup detail untuk digunakan langsung pada generator gambar.
      Prompt gambar hanya merupakan instruksi visual dan tidak boleh mengganggu alur artikel.

      META DESCRIPTION:
      Buat meta description maksimal 150 karakter.
      Meta description harus:
      - natural,
      - relevan dengan artikel,
      - menjelaskan nilai utama artikel,
      - menarik untuk diklik,
      - tidak terasa seperti kumpulan keyword.
      Jangan sekadar menggabungkan judul dengan focus keyword.
      Focus keyword boleh digunakan jika terdengar natural.

      EXCERPT:
      Buat excerpt singkat yang:
      - natural,
      - informatif,
      - menarik untuk preview artikel,
      - menggambarkan isi artikel.
      Jangan mengulang judul artikel secara mentah.

      KUALITAS DAN GAYA MANUSIA:
      Artikel harus terasa seperti ditulis oleh penulis manusia yang memahami topik.
      Jangan mencoba "terlihat seperti manusia" dengan sengaja menggunakan slang berlebihan atau kesalahan grammar.
      Human-like writing berarti:
      - alurnya masuk akal,
      - pilihan kata tidak monoton,
      - transisi antaride natural,
      - paragraf memiliki tujuan,
      - tidak semua kalimat memiliki struktur yang sama,
      - tidak semua bagian memiliki panjang yang sama,
      - tidak ada filler,
      - tidak ada pengulangan yang tidak perlu.
      Hindari bahasa promosi generik dan hiperbola seperti:
      - "sangat luar biasa",
      - "wajib dicoba",
      - "solusi terbaik",
      - "tak tertandingi",
      - "bukan sekadar",
      - "tidak hanya ... tetapi juga",
      - dan pola bahasa promosi sejenis.
      Jangan berusaha menghindari daftar kata tertentu secara mekanis.
      Yang harus dihindari adalah gaya bahasa generik, hiperbolis, repetitif, dan terasa seperti template.

      KONTROL KUALITAS INTERNAL:
      Sebelum memberikan output, periksa artikel secara internal.
      Pastikan:
      1. Pembukaan langsung relevan dengan topik.
      2. Artikel menjawab search intent.
      3. Setiap section memiliki fungsi yang jelas.
      4. Tidak ada paragraf yang hanya dibuat untuk memenuhi aturan.
      5. Keyword digunakan secara natural.
      6. Tidak ada keyword stuffing.
      7. Tidak ada pengulangan ide.
      8. Tidak ada klaim yang tidak didukung riset.
      9. Heading benar-benar diperlukan.
      10. Struktur artikel terasa natural.
      11. Artikel mudah dipindai tetapi tetap enak dibaca.
      12. Artikel terasa seperti tulisan manusia, bukan template.
      13. Prompt gambar benar-benar relevan dengan section.
      14. Meta description tidak melebihi 150 karakter.
      15. HTML valid.
      16. JSON valid.
      Jika suatu aturan format bertentangan dengan kualitas tulisan, prioritaskan kualitas tulisan selama format output tetap valid.

      OUTPUT FORMAT:
      Kembalikan HANYA satu JSON object valid.
      Format:
      {
        "content": "HTML artikel",
        "meta_description": "Meta description",
        "excerpt": "Excerpt"
      }
      Jangan memberikan teks apa pun di luar JSON.
      Jangan gunakan markdown code block.
      Nilai "content" hanya berisi body artikel HTML.
      Pastikan seluruh double quote yang diperlukan di dalam nilai JSON telah di-escape dengan benar agar JSON valid.
      Pastikan output dapat langsung diproses menggunakan json_decode() PHP.
    PROMPT;

    $userPrompt = <<<PROMPT
      JUDUL: {$selectedData['title']}
      FOCUS KEYWORD: {$selectedData['keyword']}
      HASIL RISET: {$query->research_summary}

      Gunakan hasil riset di atas sebagai basis artikel.
      Jangan menyalin atau sekadar memparafrasekan research summary.
      Sintesis informasi yang relevan lalu tulis artikel baru dengan struktur, alur, dan gaya bahasa yang natural.

      Utamakan:
      - kebutuhan pembaca,
      - search intent,
      - kejelasan,
      - akurasi,
      - keterbacaan,
      - dan kualitas tulisan.

      Jangan memasukkan fakta atau klaim yang tidak didukung oleh hasil riset.
      Output harus mengikuti format JSON yang telah ditentukan pada system instruction.
    PROMPT;

    //* 2. Call ChatGPT
    $rawResponse = $this->openai->generateContent($systemPrompt, $userPrompt, $this->modelOpenaiWriting, []);
    if (!$rawResponse) {
      throw new Exception("Failed to generate article from Models.");
    }

    //! CLEAN RESPONSE
    $cleanResponse = trim($rawResponse);
    $cleanResponse = preg_replace('/^```(?:json)?\s*/i', '', $cleanResponse);
    $cleanResponse = preg_replace('/\s*```$/','',$cleanResponse);
    $cleanResponse = trim($cleanResponse);

    //! DECODE JSON
    $decoded = json_decode($cleanResponse, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
      throw new Exception("Generated article output is not valid JSON: " . json_last_error_msg() . " | Raw response: " . substr($rawResponse, 0, 500));
    }

    //! VALIDASI
    if (!isset($decoded['content']) || !is_string($decoded['content'])) {
      throw new Exception("Generated article content is missing or invalid.");
    }
    if (!isset($decoded['meta_description']) || !is_string($decoded['meta_description'])) {
      $decoded['meta_description'] = '';
    }
    if (!isset($decoded['excerpt']) || !is_string($decoded['excerpt'])) {
      $decoded['excerpt'] = '';
    }

    //! FORCE INTERNAL DATA & ENCODE JSON
    $decoded['title']   = $selectedData['title'] ?? '';
    $decoded['keyword'] = $selectedData['keyword'] ?? '';
    $contentToSave      = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($contentToSave === false) {
      throw new Exception("Failed to encode generated article content: " . json_last_error_msg());
    }

    //* 3. Update DB
    $query->update(['generated_article_content' => $contentToSave]);

    return [
      'uuid'   => $input['uuid'],
      'result' => $decoded ? $decoded : $rawResponse
    ];
  }

  /**
   * Retrieve Session Data
   */
  public function detail(array $input)
  {
    $query = ArticleAgent::where('id_parent', $input['id_article'])->first();
    if (!$query) {
      throw new Exception("Article Agent not found.");
    }

    $data = [
      'uuid'                      => $query->id ?? null,
      'user_input_topic'          => $query->user_input_topic ?? null,
      'brainstorming_options'     => $query->brainstorming_options ?? null,
      'selected_option_data'      => $query->selected_option_data ?? null,
      'research_raw_tavily'       => json_decode($query->research_raw_tavily ?? '', true) ?: null,
      'research_summary'          => $query->research_summary ?? null,
      'generated_article_content' => json_decode($query->generated_article_content ?? '', true) ?: null,
    ];

    return $data;
  }
}
