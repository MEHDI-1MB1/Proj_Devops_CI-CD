resource "kubernetes_config_map" "app_config" {
  metadata {
    name      = "codes-cite-config"
    namespace = kubernetes_namespace.app.metadata[0].name
  }

  data = {
    DB_HOST = "mysql-service"
    DB_NAME = "gestion_reclamations"
    DB_USER = "user"
  }
}
