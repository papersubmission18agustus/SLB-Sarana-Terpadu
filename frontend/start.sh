#!/usr/bin/env bash
set -e

npm install
npm run build
npx serve -s dist -l ${PORT:-4173}
