import { api, store } from "../api.js";
import { toast } from "../ui.js";

export default function loginPage() {
  setTimeout(() => {
    const form = document.getElementById("login-form");
    form.onsubmit = async (e) => {
      e.preventDefault();
      const fd = new FormData(form);
      try {
        const { data } = await api.login(fd.get("email"), fd.get("password"));
        store.set(data.access_token, data.refresh_token);
        store.setUser(data.user);
        toast("Login OK");
        location.hash = "#/";
        location.reload();
      } catch (e) {
        toast(e.message, false);
      }
    };
  });

  return `
    <h2>Login</h2>
    <form id="login-form">
      <label>Email: <input name="email" type="email" value="admin@galonku.com" required /></label><br/>
      <label>Password: <input name="password" type="password" value="admin123" required /></label><br/>
      <button type="submit">Login</button>
    </form>
    <p><small>Default: admin@galonku.com / admin123</small></p>
  `;
}
