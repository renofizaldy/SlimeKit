variable "gcp_project_id" {
  type = string
}

variable "gcp_region" {
  type = string
}

variable "gcs_bucket_name" {
  type = string
}

variable "cloud_run_name" {
  type = string
}

variable "repo_name" {
  type = string
}

variable "neon_db_name" {
  type = string
}

variable "neon_region" {
  type = string
}

variable "image_tag" {
  type = string
}

variable "cloudinary_url" {
  type = string
  sensitive = true
}

variable "sym_key" {
  type = string
  sensitive = true
}

variable "smtp_user" {
  type = string
  sensitive = true
}

variable "smtp_pass" {
  type = string
  sensitive = true
}

variable "valkey_username" {
  type = string
  sensitive = true
}

variable "valkey_password" {
  type = string
  sensitive = true
}

variable "neon_api_key" {
  type = string
  sensitive = true
}