import preset from '../../../../vendor/filament/filament/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './resources/views/components/asas/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
        './vendor/leandrocfe/filament-apex-charts/resources/**/*.blade.php',
    ],
    theme: {
        extend: {
            colors: {
                asas: {
                    primary: {
                        50: '#ECFBF5',
                        100: '#CFF4E5',
                        200: '#A2E9CD',
                        300: '#6BD8B0',
                        400: '#36C291',
                        500: '#15A878',
                        600: '#0E8A63',
                        700: '#0C6E50',
                        800: '#0C5740',
                        900: '#093F2F',
                    },
                    info: { 50: '#EAF3FB', 500: '#2D7FC4', 700: '#184B7E' },
                    success: { 50: '#E9F7EE', 500: '#1F9D57', 700: '#0F6E3A' },
                    warning: { 50: '#FEF5E6', 500: '#E2A00F', 700: '#8A5E00' },
                    danger: { 50: '#FCECEA', 500: '#E04B43', 700: '#8F231D' },
                    gray: {
                        50: '#F5F7F7',
                        100: '#EDF0F0',
                        200: '#E1E5E5',
                        300: '#CCD2D2',
                        400: '#9BA3A3',
                        500: '#6C7474',
                        600: '#4C5252',
                        700: '#363B3B',
                        800: '#232727',
                        900: '#141717',
                    },
                },
            },
        },
    },
}
