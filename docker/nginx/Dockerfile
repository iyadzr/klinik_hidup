FROM nginx:alpine

# Copy nginx configuration
COPY docker/nginx/clinic.conf /etc/nginx/conf.d/default.conf

# Copy public files from the app build
COPY --from=clinic-management-system-app /var/www/html/public /var/www/html/public
COPY --from=clinic-management-system-app /var/www/html/templates /var/www/html/templates

# Set proper permissions
RUN chown -R nginx:nginx /var/www/html

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]