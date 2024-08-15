const User = '';
const AccessToken = '';
const RefreshToken = '';

function getToken(type) {
  return localStorage.getItem(type);
}

function setToken(type, token) {
  return localStorage.setItem(type, token);
}

function removeToken(type) {
  return localStorage.removeItem(type);
}

function loginUser(token) {
  //let user = parseJwt(token.access);
  //console.log(user);

  setToken("AccessToken", token.access);
  setToken("RefreshToken", token.refresh);
  //setToken("Ecs_User", user.user_id);
  setToken("Ecs_User", token.user_id);
}

function destorySession() {
  removeToken("AccessToken");
  removeToken("RefreshToken");
  removeToken("Ecs_User");
}

function parseJwt(token) {
  var base64Url = token.split('.')[1];
  var base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
  var jsonPayload = decodeURIComponent(atob(base64).split('').map((c) => {
    return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
  }).join(''));

  return JSON.parse(jsonPayload);
}

export { getToken, setToken, removeToken, parseJwt, loginUser, destorySession }
