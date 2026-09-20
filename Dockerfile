FROM php:8.3-apache

RUN apt-get update && apt-get install -y --no-install-recommends libonig-dev \
    && docker-php-ext-install pdo_mysql mbstring \
    && apt-get clean && rm -rf /var/lib/apt/lists/* \
    && a2enmod headers rewrite \
    && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && printf 'ServerTokens Prod\nServerSignature Off\n' > /etc/apache2/conf-available/security-local.conf \
    && a2enconf security-local

WORKDIR /var/www/html
COPY . /var/www/html/
RUN mkdir -p /var/www/html/public/assets/css && cp /var/www/html/assets/css/*.css /var/www/html/public/assets/css/
RUN chown -R www-data:www-data /var/www/html
EXPOSE 80
HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 CMD php -r '$x=@file_get_contents("http://127.0.0.1/health.php"); exit($x !== false && json_decode($x, true)["status"] === "healthy" ? 0 : 1);'
