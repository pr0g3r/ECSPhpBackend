# ECSPhpBackend
An implementation of ECS that replaces the Django backend with a pure PHP backend

## Project structure
- Vue.js front end is located in `/ecs/frontend`
  - Views, login, authorization, routers, etc. are stored in `/ecs/fronend/src`

- Php backend is located in `/ecs/backend`

- Old Django files are located in `/ecs/source`, `/ecs/venv`, etc

## Project setup
1) Run npm install to install packages
2) Open terminal and navigate into `/ecs/backend`
3) Start php server on port 8001 with the command 
```
php -S 127.0.0.1:8000
``` 
4) Navigate into `/ecs/frontend`
5) Build and start vue app by running
```
npm run build && npm run dev
```
6) Open the link that appears in the terminal to use the app in the browser

### Notes:
- Check `/ecs/frontend/.env` and ensure paths are correct
    - `VITE_PHP_API_URL` Should be the url to the backend, when running the php backend server ensure the link displayed in the terminal matches this variable
- To run with the old Django server:
    - Stop the php backend server (if running)
    - Navigate to `/ecs`
    - Run the command
    ```
    python manage.py runserver
    ```
    - Make sure `VITE_PHP_API_URL` is the same as the url and port the Django backend is running on
    - Some files such as `request.js` and `auth.js` may have to be changed, use the browser console and debugger

## Daily notes
+ Removed JWT parsing from auth.js, backend sends user id through token.
