import { api, store } from "./api.js";
import { route, navigate, startRouter } from "./router.js";

import loginPage from "./pages/login.js";
import dashboardPage from "./pages/dashboard.js";
import productsPage from "./pages/products.js";
import customersPage from "./pages/customers.js";
import transactionsPage from "./pages/transactions.js";
import schedulesPage from "./pages/schedules.js";
import usersPage from "./pages/users.js";
import rolesPage from "./pages/roles.js";
import logsPage from "./pages/logs.js";
import stockMovementsPage from "./pages/stock-movements.js";

route("/login", loginPage);
route("/", dashboardPage);
route("/products", productsPage);
route("/customers", customersPage);
route("/transactions", transactionsPage);
route("/schedules", schedulesPage);
route("/users", usersPage);
route("/roles", rolesPage);
route("/logs", logsPage);
route("/stock-movements", stockMovementsPage);

function renderNav() {
  const nav = document.getElementById("nav");
  const user = store.getUser();
  if (!user) {
    nav.innerHTML = "";
    return;
  }

  const isAdmin = user.role_name === "administrator";
  const links = [
    ["/", "Dashboard"],
    ["/products", "Produk"],
    ["/customers", "Customer"],
    ["/transactions", "Transaksi"],
    ["/schedules", "Jadwal"],
    ...(isAdmin
      ? [
          ["/users", "User"],
          ["/stock-movements", "Riwayat Stok"],
          ["/roles", "Role"],
          ["/logs", "Log"],
        ]
      : []),
  ];

  nav.innerHTML =
    links.map(([h, l]) => `<a href="#${h}">${l}</a>`).join("") +
    `<span style="float:right">${user.name} (${user.role_name}) <button id="logout">Logout</button></span>`;

  document.getElementById("logout").onclick = async () => {
    try {
      await api.logout();
    } catch {}
    store.clear();
    navigate("/login");
  };
}

function authGuard(path) {
  if (!store.access && path !== "/login") {
    navigate("/login");
    return false;
  }
  if (store.access && path === "/login") {
    navigate("/");
    return false;
  }
  return true;
}

if (store.access && !store.getUser()) {
  api
    .me()
    .then((r) => store.setUser(r.data))
    .catch(() => store.clear());
}

window.addEventListener("hashchange", renderNav);
renderNav();
startRouter(authGuard);
