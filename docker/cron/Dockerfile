ARG BASE_IMAGE

FROM ${BASE_IMAGE}

USER root

RUN apk update && apk add --no-cache busybox-suid

COPY crontab /etc/crontabs/root

RUN chmod 0600 /etc/crontabs/root && chown root:root /etc/crontabs/root

COPY --chmod=755 entrypoint.sh /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
