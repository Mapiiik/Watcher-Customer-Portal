# The production image, with what the walk through the pages needs besides: socat for reaching the
# development CRM, whose web server answers to localhost only, and every warning shown on the page,
# where the walk sees it.
ARG BASE=watcher-customer-portal:local
FROM ${BASE}
USER root
RUN apk add --no-cache socat \
 && echo "display_errors = On" > $PHP_INI_DIR/conf.d/99-test.ini
USER application
