import { defineConfig } from "vite";

export default defineConfig({
  root: ".", // dossier racine (peut être modifié si besoin)
  server: {
    port: 3000,
    open: true, // Ouvre le navigateur automatiquement
  },
});
