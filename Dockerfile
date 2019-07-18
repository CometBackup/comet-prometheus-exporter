FROM php:7.3-alpine
COPY wwwroot /wwwroot
ENTRYPOINT cd /wwwroot && php -S 0.0.0.0:80
