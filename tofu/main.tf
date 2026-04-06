resource "google_cloud_run_v2_service" "slimekit" {
  name     = var.cloud_run_name
  location = var.gcp_region

  template {
    timeout = "300s"
    max_instance_request_concurrency = 80
    scaling {
      max_instance_count = 2
    }

    containers {
      # Mengambil image hasil build GitHub Actions
      image = "${var.gcp_region}-docker.pkg.dev/${var.gcp_project_id}/${var.repo_name}/${var.cloud_run_name}:${var.image_tag}"
      
      ports {
        container_port = 80
      }
      resources {
        limits = { 
          cpu    = "1"
          memory = "1024Mi"
        }
        cpu_idle = true
        startup_cpu_boost = true
      }

      # --- ENV STATIS ---
      env {
        name  = "ENVIRONMENT"
        value = "PRODUCTION"
      }
      env {
        name  = "BASE_PATH"
        value = "/"
      }
      env {
        name  = "APP_NAME"
        value = "slimekit"
      }
      env {
        name  = "DB_HOST"
        value = neon_project.slimekit_db.database_host
      }
      env {
        name  = "DB_PORT"
        value = "5432"
      }
      env {
        name  = "DB_USER"
        value = neon_project.slimekit_db.database_user
      }
      env {
        name  = "DB_PASS"
        value = neon_project.slimekit_db.database_password
      }
      env {
        name  = "DB_NAME"
        value = neon_project.slimekit_db.database_name
      }
      env {
        name  = "DB_CLIENT"
        value = "pgsql"
      }
      env {
        name  = "SMTP_HOST"
        value = "in-v3.mailjet.com"
      }
      env {
        name  = "SMTP_PORT"
        value = "587"
      }
      env {
        name  = "SMTP_USER"
        value = var.smtp_user
      }
      env {
        name  = "SMTP_PASS"
        value = var.smtp_pass
      }
      env {
        name  = "MAIL_FROM"
        value = "info@lolitamakeup.id"
      }
      env {
        name  = "MAIL_FROM_NAME"
        value = "Slimekit"
      }
      env {
        name  = "CLOUDINARY_URL"
        value = var.cloudinary_url
      }
      env {
        name  = "SYM_KEY"
        value = var.sym_key
      }
      env {
        name  = "VALKEY_SCHEME"
        value = "tcp"
      }
      env {
        name  = "VALKEY_HOST"
        value = ""
      }
      env {
        name  = "VALKEY_PORT"
        value = ""
      }
      env {
        name  = "VALKEY_USERNAME"
        value = var.valkey_username
      }
      env {
        name  = "VALKEY_PASSWORD"
        value = var.valkey_password
      }
    }
  }

  traffic {
    type    = "TRAFFIC_TARGET_ALLOCATION_TYPE_LATEST"
    percent = 100
  }
}

locals {
  api = google_cloud_run_v2_service.slimekit
}

resource "google_cloud_run_v2_service_iam_member" "public_access" {
  project  = local.api.project
  location = local.api.location
  name     = local.api.name
  role     = "roles/run.invoker"
  member   = "allUsers"
}