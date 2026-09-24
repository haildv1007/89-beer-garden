export default {
    plugins: ['@shufo/prettier-plugin-blade', '@prettier/plugin-php'],
    printWidth: 120,
    tabWidth: 4,
    singleQuote: true,
    trailingComma: 'all',
    overrides: [
        {
            files: '*.blade.php',
            options: {
                parser: 'blade',
                wrapAttributes: 'auto',
            },
        },
        {
            files: [
                'app/**/*.php',
                'bootstrap/*.php',
                'config/**/*.php',
                'database/**/*.php',
                'lang/**/*.php',
                'routes/**/*.php',
                'tests/**/*.php',
            ],
            options: {
                parser: 'php',
            },
        },
    ],
};
