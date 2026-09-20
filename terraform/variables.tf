variable "aws_region" {
  type    = string
  default = "us-east-1"
}
variable "project" {
  type    = string
  default = "cloudops-incident-tracker"
}
variable "environment" {
  type    = string
  default = "lab"
}
variable "vpc_cidr" {
  type    = string
  default = "10.42.0.0/16"
}
variable "instance_type" {
  type    = string
  default = "t3.small"
}
variable "repository_url" {
  description = "Public HTTPS Git URL of this project. The EC2 bootstrap clones this repository."
  type        = string
  validation {
    condition     = can(regex("^https://[^ ]+\\.git$", var.repository_url))
    error_message = "Use a public HTTPS Git URL ending in .git."
  }
}
variable "alarm_email" {
  type        = string
  default     = ""
  description = "Optional email for SNS alarm notifications; subscription requires confirmation."
}
variable "log_retention_days" {
  type    = number
  default = 14
}
