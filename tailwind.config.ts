import type { Config } from "tailwindcss";

const config: Config = {
  content: [
    "./pages/**/*.{js,ts,jsx,tsx,mdx}",
    "./components/**/*.{js,ts,jsx,tsx,mdx}",
    "./app/**/*.{js,ts,jsx,tsx,mdx}",
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: "#21808D",
          hover: "#1D7480",
          active: "#1A6873",
        },
        secondary: {
          DEFAULT: "rgba(94, 82, 64, 0.12)",
          hover: "rgba(94, 82, 64, 0.2)",
        },
        background: "#FCFCF9",
        surface: "#FFFFFD",
        text: {
          DEFAULT: "#13343B",
          secondary: "#626C71",
        },
      },
      fontFamily: {
        sans: ['var(--font-inter)', 'system-ui', 'sans-serif'],
      },
    },
  },
  plugins: [],
};
export default config;



