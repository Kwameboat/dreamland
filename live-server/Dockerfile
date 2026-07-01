FROM node:20-bookworm-slim

RUN apt-get update \
  && apt-get install -y --no-install-recommends python3 build-essential \
  && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm ci --omit=dev 2>/dev/null || npm install --omit=dev

COPY . .

ENV NODE_ENV=production
ENV DREAMLAND_LIVE_PORT=4443

EXPOSE 4443
EXPOSE 40000-40100/udp

CMD ["npm", "start"]
