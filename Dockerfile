FROM php:8.3-cli-bookworm
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev ca-certificates \
    && docker-php-ext-install pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*
ENV PGSSLROOTCERT=/etc/ssl/certs/ca-certificates.crt
WORKDIR /app
COPY public/ public/
CMD ["sh", "-c", "exec php -S 0.0.0.0:${PORT:-10000} -t public"]
