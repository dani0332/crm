import laravel from "laravel-vite-plugin";
import { defineConfig, loadEnv } from "vite";
import { sync } from "glob";

function getWorkerFiles() {
    return sync("resources/js/**/*.worker.js").reduce((entries, file) => {
        const name = file.replace(/^.*[\\/]/, "").replace(".js", "");
        entries[name] = file;
        return entries;
    }, {});
}

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), "");
    const watchWorkers = env.WATCH_WORKER === "true" || false;

    return {
        plugins: [
            laravel({
                input: [],
                publicDirectory: "public",
                buildDirectory: "build/workers",
            }),
        ],
        build: {
            manifest: false,
            rollupOptions: {
                input: getWorkerFiles(),
                output: {
                    entryFileNames: "[name].js",
                },
            },
            watch: watchWorkers
                ? {
                      include: "resources/js/**/*.worker.js",
                  }
                : undefined,
        },
    };
});
