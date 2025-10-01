FROM python:3.11-slim

WORKDIR /app
COPY setup.py .
# If your package code is in src/ or a package dir, copy it:
# COPY src/ ./src
# Or just copy whole backend if simpler:
COPY . .

RUN apt-get update \
    && apt-get install -y --no-install-recommends build-essential libmariadb-dev pkg-config \
    && pip install --no-cache-dir . \
    && apt-get purge -y build-essential pkg-config \
    && apt-get autoremove -y \
    && rm -rf /var/lib/apt/lists/*

ENV PYTHONUNBUFFERED=1

CMD ["python", "dh_updater.py"]
