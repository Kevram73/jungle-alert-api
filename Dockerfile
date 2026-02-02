# Multi-stage build pour optimiser la taille de l'image
FROM python:3.11-slim as builder

# Définir le répertoire de travail
WORKDIR /app

# Installer les dépendances de build
RUN apt-get update && apt-get install -y --no-install-recommends \
    gcc \
    g++ \
    && rm -rf /var/lib/apt/lists/*

# Copier et installer les dépendances Python
COPY requirements.txt .
RUN pip install --no-cache-dir --user -r requirements.txt

# Stage final
FROM python:3.11-slim

# Créer un utilisateur non-root pour la sécurité
RUN groupadd -r appuser && useradd -r -g appuser appuser

# Définir le répertoire de travail
WORKDIR /app

# Installer les dépendances système nécessaires pour Chrome et Selenium
RUN apt-get update && apt-get install -y --no-install-recommends \
    wget \
    gnupg \
    curl \
    ca-certificates \
    fonts-liberation \
    libasound2 \
    libatk-bridge2.0-0 \
    libatk1.0-0 \
    libatspi2.0-0 \
    libcups2 \
    libdbus-1-3 \
    libdrm2 \
    libgbm1 \
    libgtk-3-0 \
    libnspr4 \
    libnss3 \
    libwayland-client0 \
    libxcomposite1 \
    libxdamage1 \
    libxfixes3 \
    libxkbcommon0 \
    libxrandr2 \
    xdg-utils \
    libu2f-udev \
    libvulkan1 \
    && rm -rf /var/lib/apt/lists/*

# Installer Google Chrome
RUN wget --no-check-certificate --timeout=30 --tries=3 -q -O /tmp/chrome.deb \
    https://dl.google.com/linux/direct/google-chrome-stable_current_amd64.deb \
    && apt-get update \
    && apt-get install -y /tmp/chrome.deb \
    && rm -f /tmp/chrome.deb \
    && rm -rf /var/lib/apt/lists/*

# Vérifier l'installation de Chrome
RUN google-chrome --version

# Copier les dépendances Python depuis le builder
COPY --from=builder /root/.local /root/.local

# S'assurer que les scripts sont dans le PATH
ENV PATH=/root/.local/bin:$PATH

# Copier le code de l'application
COPY --chown=appuser:appuser . .

# Créer les répertoires nécessaires
RUN mkdir -p /app/output /app/logs && \
    chown -R appuser:appuser /app/output /app/logs && \
    chmod 755 /app/output /app/logs

# Définir les variables d'environnement
ENV PYTHONUNBUFFERED=1
ENV FLASK_APP=run.py
ENV FLASK_ENV=production
ENV DISPLAY=:99

# Exposer le port de l'API
EXPOSE 5000

# Passer à l'utilisateur non-root
USER appuser

# Healthcheck
HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
    CMD curl -f http://localhost:5000/api/health || exit 1

# Commande par défaut - démarrer l'application avec gunicorn
CMD ["gunicorn", "--bind", "0.0.0.0:5000", "--workers", "4", "--threads", "2", "--timeout", "120", "--access-logfile", "-", "--error-logfile", "-", "run:app"]











