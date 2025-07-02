#!/bin/bash
set -e

echo "🔄 Pulling latest code from Git..."
git pull origin main

echo "📦 Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader

echo "📥 Installing Node dependencies..."
npm ci

echo "🛠️ Building frontend (SSR)..."
npm run build:ssr

echo "⚡ Optimizing Laravel application..."
php artisan optimize

echo "✅ Deployment complete!"
