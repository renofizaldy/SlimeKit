resource "neon_project" "slimekit_db" {
  name      = var.db_name
  region_id = var.neon_region
  history_retention_seconds = 21600
}

output "database_url" {
  value       = "postgres://${neon_project.slimekit_db.database_user}:${neon_project.slimekit_db.database_password}@${neon_project.slimekit_db.database_host}/${neon_project.slimekit_db.database_name}"
  sensitive   = true
  description = "URL koneksi PostgreSQL untuk GitHub Actions"
}