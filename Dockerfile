FROM php:7.3-alpine
COPY wwwroot /wwwroot
WORKDIR /wwwroot
ENTRYPOINT [ "/usr/local/bin/php", "-S", "0.0.0.0:80" ]
