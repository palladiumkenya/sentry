FROM alpine:3.13

RUN apk update && apk upgrade

RUN apk add curl \
  git \
  autoconf \
  make \
  g++ \
  unixodbc-dev \
  nginx \
  redis \
  supervisor

RUN apk add php7 \
  php7-pear \
  php7-dev \
  php7-bcmath \
  php7-ctype \
  php7-curl \
  php7-dom \
  php7-exif \
  php7-fileinfo \
  php7-fpm \
  php7-gd \
  php7-iconv \
  php7-json \
  php7-mbstring \
  php7-openssl \
  php7-pcntl \
  php7-pdo \
  php7-pdo_pgsql \
  php7-pdo_mysql \
  php7-phar \
  php7-posix \
  php7-redis \
  php7-session \
  php7-simplexml \
  php7-sockets \
  php7-tokenizer \
  php7-xml \
  php7-xmlreader \
  php7-xmlwriter \
  php7-zip

RUN curl -O https://download.microsoft.com/download/e/4/e/e4e67866-dffd-428c-aac7-8d28ddafb39b/msodbcsql17_17.7.1.1-1_amd64.apk
RUN curl -O https://download.microsoft.com/download/e/4/e/e4e67866-dffd-428c-aac7-8d28ddafb39b/mssql-tools_17.7.1.1-1_amd64.apk
RUN apk add --allow-untrusted msodbcsql17_17.7.1.1-1_amd64.apk
RUN apk add --allow-untrusted mssql-tools_17.7.1.1-1_amd64.apk

RUN pecl install redis
RUN pecl install sqlsrv
RUN pecl install pdo_sqlsrv

RUN sed -i "s/request_terminate_timeout =.*/request_terminate_timeout=600/g" /etc/php7/php.ini && \
  sed -i "s/default_socket_timeout =.*/default_socket_timeout=600/g" /etc/php7/php.ini && \
  sed -i "s/max_input_time =.*/max_input_time=600/g" /etc/php7/php.ini && \
  sed -i "s/max_execution_time =.*/max_execution_time=600/g" /etc/php7/php.ini && \
  sed -i "s/upload_max_filesize =.*/upload_max_filesize=100M/g" /etc/php7/php.ini && \
  sed -i "s/post_max_size =.*/post_max_size=100M/g" /etc/php7/php.ini && \
  sed -i "s/memory_limit =.*/memory_limit=2G/g" /etc/php7/php.ini && \
  sed -i "s/cgi.fix_pathinfo=/cgi.fix_pathinfo=0#/g" /etc/php7/php.ini && \
  echo "daemon off;" >> /etc/nginx/nginx.conf && \
  echo extension=pdo_sqlsrv.so >> `php --ini | grep "Scan for additional .ini files" | sed -e "s|.*:\s*||"`/10_pdo_sqlsrv.ini && \
  echo extension=sqlsrv.so >> `php --ini | grep "Scan for additional .ini files" | sed -e "s|.*:\s*||"`/00_sqlsrv.ini && \
  mkdir -p /run/nginx && \
  mkdir /etc/supervisor.d

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer --quiet

RUN echo -e "server { \n\
	listen 80 default_server; \n\
	listen [::]:80 default_server ipv6only=on; \n\
  server_tokens off; \n\
  client_max_body_size 100m; \n\
	root /var/www/html/public; \n\
  add_header X-Frame-Options \"SAMEORIGIN\"; \n\
  add_header X-XSS-Protection \"1; mode=block\"; \n\
  add_header X-Content-Type-Options \"nosniff\"; \n\
  index index.html index.htm index.php; \n\
  charset utf-8; \n\
	location / { \n\
		try_files \$uri \$uri/ /index.php?\$query_string; \n\
	} \n\
  location = /favicon.ico { access_log off; log_not_found off; } \n\
  location = /robots.txt  { access_log off; log_not_found off; } \n\
  error_page 404 /index.php; \n\
	location ~ \.php$ { \n\
		fastcgi_pass 127.0.0.1:9000; \n\
		fastcgi_index index.php; \n\
		fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name; \n\
    fastcgi_param  HTTPS \"on\"; \n\
		include fastcgi_params; \n\
	} \n\
  location ~ /\.(?!well-known).* { \n\
		deny all; \n\
	} \n\
}" > /etc/nginx/conf.d/default.conf

RUN echo -e "[supervisord] \n\
nodaemon = true \n\
[program:pre] \n\
command = /bin/sh -c \"rm -Rf /var/www/html/vendor/laravel/nova && /usr/local/bin/composer install --no-interaction --optimize-autoloader --prefer-dist && chmod -R 777 /var/www/html/storage && /usr/bin/php /var/www/html/artisan config:clear && /usr/bin/php /var/www/html/artisan cache:clear && /usr/bin/php /var/www/html/artisan migrate --force && /usr/bin/php /var/www/html/artisan config:cache && supervisorctl start horizon\" \n\
autostart = true \n\
autorestart = false \n\
stdout_logfile=/dev/stdout \n\
stdout_logfile_maxbytes=0 \n\
stderr_logfile=/dev/stderr \n\
stderr_logfile_maxbytes=0 \n\
[program:nginx] \n\
command = /usr/sbin/nginx \n\
autostart = true \n\
stderr_logfile=/dev/stderr \n\
stderr_logfile_maxbytes=0 \n\
[program:php-fpm] \n\
command = /usr/sbin/php-fpm7 -F \n\
autostart = true \n\
stderr_logfile=/dev/stderr \n\
stderr_logfile_maxbytes=0 \n\
[program:redis] \n\
command = /usr/bin/redis-server --appendonly no --save "" \n\
autostart = true \n\
stderr_logfile=/dev/stderr \n\
stderr_logfile_maxbytes=0 \n\
[program:cron] \n\
command = /usr/sbin/crond -f \n\
autostart = true \n\
stderr_logfile=/dev/stderr \n\
stderr_logfile_maxbytes=0 \n\
[program:horizon] \n\
command = /usr/bin/php /var/www/html/artisan horizon \n\
autostart = false \n\
autorestart = true \n\
stderr_logfile=/dev/stderr \n\
stderr_logfile_maxbytes=0 \n\
" > /etc/supervisor.d/supervisor.ini

RUN touch crontab.tmp \
    && echo '* * * * * /usr/bin/php7 /var/www/html/artisan schedule:run >> /dev/null 2>&1' >> crontab.tmp \
    && crontab crontab.tmp \
    && rm -rf crontab.tmp

COPY . /var/www/html

WORKDIR /var/www/html

VOLUME ["/var/www/html"]

EXPOSE 80

CMD ["/usr/bin/supervisord"]
