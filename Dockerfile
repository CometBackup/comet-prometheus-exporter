FROM php:7.3-alpine
COPY approot /approot
WORKDIR /approot/wwwroot
ENTRYPOINT [ "/usr/local/bin/php", "-S", "0.0.0.0:80" ]
