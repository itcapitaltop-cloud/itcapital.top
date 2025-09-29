/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
    ],
    safelist: [
        {
            pattern: /opacity-\[(0(\.\d+)?|1(\.0+)?)\]/,
        },
    ],
    theme: {
        extend: {
            rotate: {
                '12': '12deg',
                '18': '18deg',
                '-12': '-12deg',
                '-18': '-18deg',
            },
            colors: {
                gray: {
                    DEFAULT: "#1F1F1F",
                    450: "#232323",
                    400: "#373737",
                    300: "#7C7C7C",
                },
                black: {
                    DEFAULT: "#060911",
                },
                blue: {
                    600: "#0400C2",
                    DEFAULT: "#0030DC",
                },
                aqua: {
                    DEFAULT: "#63CDCD",
                },
                white: {
                    600: "#CCCCCC",
                    DEFAULT: "#fff",
                },
                red: {
                    600: "#8F2B2B",
                    DEFAULT: "#FF3737",
                },
                yellow: {
                    DEFAULT: "#E4D480",
                },
                green: {
                    DEFAULT: "#6BC25C",
                },
                pink: {
                    DEFAULT: "#F38FDD",
                },
                purple: {
                    DEFAULT: "#8673D2",
                },
                lime: {
                    DEFAULT: '#B4FF59',
                }
            },
            container: {
                center: true,
                padding: "1rem",
                screens: {
                    xl: "1200px",
                },
            },
            animation: {
                marquee: 'marquee 20s linear infinite',
                'shine-sweep': 'shine-sweep 10s linear infinite',
                'comet-hero': 'comet-hero linear 1',

            },
            keyframes: {
                marquee: {
                    '0%': { transform: 'translateX(0%)' },
                    '100%': { transform: 'translateX(-50%)' },
                },
                'shine-sweep': {
                    '0%':   { transform: 'translateX(-110%)' },
                    '7%':   { transform: 'translateX(440%)' },
                    '8%':   { transform: 'translateX(440%)' },
                    '100%': { transform: 'translateX(440%)' },
                },
                'comet-hero': {
                    '0%':    { opacity: '0', transform: 'translate(0,0) scale(1)' },
                    '5%':    { opacity: '1', transform: 'translate(10px, -18px) scale(1.03)' },
                    '55%':   { opacity: '1', transform: 'translate(110px, -200px) scale(1.10)' },
                    '100%':  { opacity: '0', transform: 'translate(200px, -360px) scale(1.13)' },
                }
            },
            fontFamily: {
                dela: ['"Dela Gothic One"', 'sans-serif'],
                ocr: ['"OCR B"', 'sans-serif']
            },
        },
    },
    plugins: [],
};
