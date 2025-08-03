# Frontend Dockerfile for Vue.js application
FROM node:18-alpine AS build

WORKDIR /app

# Copy package files
COPY package*.json ./

# Install ALL dependencies (including devDependencies for build)
RUN npm ci

# Copy configuration files
COPY webpack.config.js ./
COPY babel.config.js ./

# Copy source code
COPY assets/ ./assets/
COPY public/ ./public/

# Build the application
RUN npm run build

# Production stage with nginx
FROM nginx:alpine

# Copy built assets
COPY --from=build /app/public/build /usr/share/nginx/html/build

# Copy public files
COPY public/ /usr/share/nginx/html/

# Copy nginx configuration
COPY docker/frontend/nginx.conf /etc/nginx/conf.d/default.conf

# Expose port
EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]