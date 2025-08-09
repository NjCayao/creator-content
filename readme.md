adminTLE 4.0
Usuario: admin@sistema.com
Contraseña: admin123
______________________________________
# Buscar tendencias cada 2 horas
0 */2 * * * /usr/bin/php /ruta/a/creator/cron/buscar-tendencias.php >> /ruta/a/creator/storage/logs/cron.log 2>&1
# Configuración del Cron:
bash# Ejecutar cada 3 horas
0 */3 * * * /usr/bin/php /ruta/a/creator/cron/generar-videos.php >> /ruta/a/logs/

O para pruebas, puedes ejecutar manualmente:
http://tudominio.com/creator/cron/buscar-tendencias.php?token=TU_TOKEN_SEGURO_AQUI_[hash]