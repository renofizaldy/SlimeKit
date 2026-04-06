resource "google_cloud_run_v2_service" "slimekit" {
  name     = var.cloud_run_name
  location = var.gcp_region

  invoker_iam_disabled = true
  
  scaling {
    max_instance_count = 2
  }

  template {
    timeout = "300s"
    max_instance_request_concurrency = 80

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
        value = var.environment
      }
      env {
        name  = "SYM_KEY"
        value = var.sym_key
      }
      env {
        name  = "BASE_PATH"
        value = var.base_path
      }
      env {
        name  = "APP_NAME"
        value = var.app_name
      }
      env {
        name  = "DB_HOST"
        value = neon_project.slimekit_db.database_host
      }
      env {
        name  = "DB_PORT"
        value = tonumber(var.db_port)
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
        value = var.db_client
      }
      env {
        name  = "SMTP_HOST"
        value = var.smtp_host
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
        name  = "SMTP_PORT"
        value = tonumber(var.smtp_port)
      }
      env {
        name  = "MAIL_FROM"
        value = var.mail_from
      }
      env {
        name  = "MAIL_FROM_NAME"
        value = var.mail_from_name
      }
      env {
        name  = "CLOUDINARY_URL"
        value = var.cloudinary_url
      }
      env {
        name  = "R2_REGION"
        value = var.r2_region
      }
      env {
        name  = "R2_ENDPOINT"
        value = var.r2_endpoint
      }
      env {
        name  = "R2_ACCESS_KEY_ID"
        value = var.r2_access_key_id
      }
      env {
        name  = "R2_SECRET_ACCESS_KEY"
        value = var.r2_secret_access_key
      }
      env {
        name  = "R2_BUCKET"
        value = var.r2_bucket
      }
      env {
        name  = "VALKEY_SCHEME"
        value = var.valkey_scheme
      }
      env {
        name  = "VALKEY_HOST"
        value = var.valkey_host
      }
      env {
        name  = "VALKEY_PORT"
        value = tonumber(var.valkey_port)
      }
      env {
        name  = "VALKEY_USERNAME"
        value = var.valkey_username
      }
      env {
        name  = "VALKEY_PASSWORD"
        value = var.valkey_password
      }
      env {
        name  = "CRONHOOKS_API_KEY"
        value = var.cronhooks_api_key
      }
      env {
        name  = "CRONHOOKS_BASE_URL"
        value = var.cronhooks_base_url
      }
      env {
        name  = "CRONHOOKS_CALLBACK"
        value = var.cronhooks_callback
      }
    }
  }

  traffic {
    type    = "TRAFFIC_TARGET_ALLOCATION_TYPE_LATEST"
    percent = 100
  }
}