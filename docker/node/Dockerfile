FROM node:22-alpine
ENV NODE_ENV=development
WORKDIR /usr/src/app
COPY package*.json ./
RUN npm i
COPY *.js .* /usr/src/app/
RUN chown -R node /usr/src/app