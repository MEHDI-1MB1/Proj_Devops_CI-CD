output "namespace" {
  value = kubernetes_namespace.app.metadata[0].name
}

output "web_service_url" {
  value = "http://localhost:30080"
}

output "mysql_service" {
  value = "${kubernetes_service.mysql.metadata[0].name}.${kubernetes_namespace.app.metadata[0].name}.svc.cluster.local"
}

output "web_replicas" {
  value = var.web_replicas
}
