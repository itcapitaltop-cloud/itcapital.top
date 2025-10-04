FROM postgres:18.0

COPY init.sh /docker-entrypoint-initdb.d/init.sh

RUN chmod +x /docker-entrypoint-initdb.d/init.sh

WORKDIR /var/lib/postgresql/data

CMD ["postgres"]
