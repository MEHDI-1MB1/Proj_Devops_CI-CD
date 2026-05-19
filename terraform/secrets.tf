resource "kubernetes_secret" "app_secrets" {
  metadata {
    name      = "codes-cite-secrets"
    namespace = kubernetes_namespace.app.metadata[0].name
  }

  data = {
    DB_PASSWORD         = base64encode(var.mysql_password)
    MYSQL_ROOT_PASSWORD = base64encode(var.mysql_root_password)
  }

  type = "Opaque"
}
