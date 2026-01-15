<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class CreateData extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * https://book.cakephp.org/phinx/0/en/seeding.html
     */
    public function run(): void
    {
        $this->execute('TRUNCATE TABLE tb_user, tb_user_role RESTART IDENTITY CASCADE');

        $rolesAdmin = [
            "dashboard:view", "dashboard:crud",
            "article:view", "article:crud",
            "content_gallery:view", "content_gallery:crud",
            "content_faq:view", "content_faq:crud",
            "content_contact:view", "content_contact:crud",
            "content_team:view", "content_team:crud",
            "setting_user:view", "setting_user:crud"
        ];
        $rolesMod = [
            "dashboard:view",
            "article:view", "article:crud",
            "content_gallery:view", "content_gallery:crud",
            "content_faq:view", "content_faq:crud",
            "content_contact:view", "content_contact:crud",
            "content_team:view", "content_team:crud"
        ];

        $dataRole = [
            [
                'label'      => 'Admin',
                'role'       => json_encode($rolesAdmin),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'label'      => 'Moderator',
                'role'       => json_encode($rolesMod),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]
        ];
        $this->table('tb_user_role')->insert($dataRole)->saveData();

        $roleAdmin = $this->fetchRow("SELECT id FROM tb_user_role WHERE label = 'Admin'");
        $idRoleAdmin = $roleAdmin['id'] ?? 1;
        $dataUser = [
            [
                'name'         => 'Demo',
                'phone'        => '081234567890',
                'email'        => 'demo@gmail.com',
                'username'     => 'demo',
                'password'     => '$2y$10$TTQNEVnH1ZN33uBxNqw69.TQl1Mwmk7vca3fYSpkGEr/fPIcpZ3vi',
                'status'       => 'active',
                'id_user_role' => $idRoleAdmin,
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ]
        ];
        $this->table('tb_user')->insert($dataUser)->saveData();
    }
}
