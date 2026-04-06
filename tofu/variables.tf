variable "gcp_project_id" {
  type = string
}

variable "gcp_region" {
  type = string
}

variable "gcs_bucket_name" {
  type = string
}

variable "repo_name" {
  type = string
}

variable "cloud_run_name" {
  type = string
}

variable "image_tag" {
  type = string
}

variable "environment" {
  type = string
}

variable "sym_key" {
  type = string
  sensitive = true
}

variable "base_path" {
  type = string
}

variable "app_name" {
  type = string
}

variable "neon_api_key" {
  type = string
  sensitive = true
}

variable "neon_region" {
  type = string
}

variable "db_name" {
  type = string
}

variable "db_port" {
  type = string
}

variable "db_client" {
  type = string
}

variable "cloudinary_url" {
  type = string
  sensitive = true
}

variable "smtp_host" {
  type = string
}

variable "smtp_port" {
  type = string
}

variable "smtp_user" {
  type = string
  sensitive = true
}

variable "smtp_pass" {
  type = string
  sensitive = true
}

variable "mail_from" {
  type = string
}

variable "mail_from_name" {
  type = string
}

variable "r2_region" {
  type = string
}

variable "r2_endpoint" {
  type = string
}

variable "r2_access_key_id" {
  type = string
  sensitive = true
}

variable "r2_secret_access_key" {
  type = string
  sensitive = true
}

variable "r2_bucket" {
  type = string
}

variable "valkey_scheme" {
  type = string
}

variable "valkey_host" {
  type = string
}

variable "valkey_port" {
  type = string
}

variable "valkey_username" {
  type = string
  sensitive = true
}

variable "valkey_password" {
  type = string
  sensitive = true
}

variable "cronhooks_api_key" {
  type = string
  sensitive = true
}

variable "cronhooks_base_url" {
  type = string
}

variable "cronhooks_callback" {
  type = string
}