output "application_url" {
  value = "http://${aws_lb.app.dns_name}"
}
output "health_url" {
  value = "http://${aws_lb.app.dns_name}/health.php"
}
output "instance_id" {
  value = aws_instance.app.id
}
output "log_group" {
  value = aws_cloudwatch_log_group.app.name
}
output "secret_arn" {
  value = aws_secretsmanager_secret.db.arn
}
