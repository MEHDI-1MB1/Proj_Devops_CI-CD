# Construire l'image Docker localement
resource "docker_image" "app" {
  name = "${var.image_name}:${var.image_tag}"
  build {
    context = "../"
    dockerfile = "../Dockerfile"
  }
}

resource "kubernetes_deployment" "web" {
  metadata {
    name      = "web"
    namespace = kubernetes_namespace.app.metadata[0].name
    labels = {
      app = "web"
    }
  }

  spec {
    replicas = var.web_replicas

    selector {
      match_labels = {
        app = "web"
      }
    }

    template {
      metadata {
        labels = {
          app = "web"
        }
      }

      spec {
        container {
          name  = "php"
          image = docker_image.app.name
          image_pull_policy = "IfNotPresent"

          port {
            container_port = 80
          }

          env {
            name = "DB_HOST"
            value_from {
              config_map_key_ref {
                name = kubernetes_config_map.app_config.metadata[0].name
                key  = "DB_HOST"
              }
            }
          }

          env {
            name = "DB_NAME"
            value_from {
              config_map_key_ref {
                name = kubernetes_config_map.app_config.metadata[0].name
                key  = "DB_NAME"
              }
            }
          }

          env {
            name = "DB_USER"
            value_from {
              config_map_key_ref {
                name = kubernetes_config_map.app_config.metadata[0].name
                key  = "DB_USER"
              }
            }
          }

          env {
            name = "DB_PASSWORD"
            value_from {
              secret_key_ref {
                name = kubernetes_secret.app_secrets.metadata[0].name
                key  = "DB_PASSWORD"
              }
            }
          }

          volume_mount {
            name       = "uploads"
            mount_path = "/var/www/html/uploads"
          }
        }

        volume {
          name = "uploads"
          empty_dir {}
        }
      }
    }
  }

  lifecycle {
    ignore_changes = [
      metadata,
      spec[0].template[0].spec[0].container[0].image,
      spec[0].replicas,
    ]
  }
}

resource "kubernetes_service" "web" {
  metadata {
    name      = "web-service"
    namespace = kubernetes_namespace.app.metadata[0].name
  }

  spec {
    selector = {
      app = "web"
    }

    port {
      port        = 80
      target_port = 80
      node_port   = 30080
    }

    type = "NodePort"
  }
}
