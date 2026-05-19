variable "namespace" {
  description = "Kubernetes namespace"
  type        = string
  default     = "codes-cite"
}

variable "image_name" {
  description = "Docker image name"
  type        = string
  default     = "codes-cite"
}

variable "image_tag" {
  description = "Docker image tag"
  type        = string
  default     = "latest"
}

variable "web_replicas" {
  description = "Number of web replicas"
  type        = number
  default     = 2
}

variable "mysql_password" {
  description = "MySQL password"
  type        = string
  sensitive   = true
  default     = "password"
}

variable "mysql_root_password" {
  description = "MySQL root password"
  type        = string
  sensitive   = true
  default     = "rootpassword"
}
