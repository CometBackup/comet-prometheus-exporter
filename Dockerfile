FROM php:7.4-alpine
COPY approot /approot
WORKDIR /approot/wwwroot
ENTRYPOINT [ "/usr/local/bin/php", "-d", "zlib.output_compression=On", "-S", "0.0.0.0:80" ]
