FROM php:8.2-apache

# Copia os arquivos do seu projeto para o servidor web
COPY . /var/www/html/

# Expõe a porta usada pelo Render
EXPOSE 10000

# Comando para iniciar o Apache
CMD ["apache2-foreground"]
