terraform {
  required_version = ">= 1.0"
  required_providers {
    kubernetes = {
      source  = "hashicorp/kubernetes"
      version = "~> 2.23"
    }
    docker = {
      source  = "kreuzwerker/docker"
      version = "~> 3.0"
    }
  }
}

# Provider Kubernetes (pour Kind)
provider "kubernetes" {
  config_path = "~/.kube/config"
}

# Provider Docker (pour builder l'image)
provider "docker" {
  host = "unix:///var/run/docker.sock"
}
