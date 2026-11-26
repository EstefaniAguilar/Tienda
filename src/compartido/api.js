const API = "/mt-tienda-virtual/backend/api.php";
let csrfToken = "";
let csrfPromise = null;

async function ensureCsrf() {
  if (csrfToken) return csrfToken;
  if (!csrfPromise) {
    csrfPromise = fetch(`${API}?action=bootstrap`, { method: "GET", credentials: "include" })
      .then(async response => {
        const data = await response.json().catch(() => ({}));
        if (!response.ok || !data.csrfToken) throw new Error(data.error || "No se pudo iniciar una sesión segura.");
        csrfToken = data.csrfToken;
        return csrfToken;
      })
      .finally(() => { csrfPromise = null; });
  }
  return csrfPromise;
}

export async function api(action, payload) {
  const isBootstrap = action === "bootstrap" && payload === undefined;
  if (!isBootstrap) await ensureCsrf();
  const request = () => fetch(`${API}?action=${encodeURIComponent(action)}`, {
    method: isBootstrap ? "GET" : "POST",
    credentials: "include",
    headers: isBootstrap
      ? {}
      : { "Content-Type": "application/json", "X-CSRF-Token": csrfToken },
    body: isBootstrap ? undefined : JSON.stringify(payload ?? {})
  });
  let response = await request();
  if (!isBootstrap && response.status === 419) {
    csrfToken = "";
    await ensureCsrf();
    response = await request();
  }
  const data = await response.json().catch(() => ({}));
  if (!response.ok) throw new Error(data.error || "No se pudo completar la operación.");
  if (data.csrfToken) csrfToken = data.csrfToken;
  return data;
}

export async function uploadProductImages(productId, files) {
  await ensureCsrf();
  const body = new FormData();
  body.append("productId", productId);
  [...files].forEach(file => body.append("images[]", file));
  const send = () => fetch(`${API}?action=upload-product-images`, {
    method: "POST", credentials: "include",
    headers: { "X-CSRF-Token": csrfToken }, body
  });
  let response = await send();
  if (response.status === 419) { csrfToken = ""; await ensureCsrf(); response = await send(); }
  const data = await response.json().catch(() => ({}));
  if (!response.ok) throw new Error(data.error || "No se pudieron cargar las imágenes.");
  return data;
}

export async function uploadReturnEvidence(returnId, files) {
  await ensureCsrf();
  const body = new FormData();
  body.append("returnId", returnId);
  [...files].forEach(file => body.append("evidence[]", file));
  const send = () => fetch(`${API}?action=upload-return-evidence`, {
    method: "POST", credentials: "include",
    headers: { "X-CSRF-Token": csrfToken }, body
  });
  let response = await send();
  if (response.status === 419) { csrfToken = ""; await ensureCsrf(); response = await send(); }
  const data = await response.json().catch(() => ({}));
  if (!response.ok) throw new Error(data.error || "No se pudo cargar la evidencia.");
  return data;
}
