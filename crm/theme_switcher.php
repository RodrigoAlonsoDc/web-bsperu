<!-- Theme Switcher Component -->
<style>
:root {
	/* dark theme */
	--dark-bg: #101214;
	--dark-border: #22272B;
	--dark-surface: #161A1D;
	--dark-text-primary: #DEE4EA;
	--dark-text-secondary: #738496;
	--dark-primary: #1D7AFC;
	--dark-text-inverse: #FFFFFF;
	
	/* sunset theme */
	--sunset-bg: #151c19;
	--sunset-border: #424f4a;
	--sunset-surface: #2f3834;
	--sunset-text-primary: #ecd2c5;
	--sunset-text-secondary: #C0AB92;
	--sunset-primary: #C0AB92;
	--sunset-text-inverse: #151c19;
	
	/* sunrise theme */
	--sunrise-bg: #ecd2c5;
	--sunrise-border: #d7c9c6;
	--sunrise-surface: #f3e8e5;
	--sunrise-text-primary: #4f2733;
	--sunrise-text-secondary: #685844;
	--sunrise-primary: #a04d66;
	--sunrise-text-inverse: #f3e8e5;
	
	/* light theme */
	--light-bg: #F7F8F9;
	--light-border: #F1F2F4;
	--light-surface: #FFFFFF;
	--light-text-primary: #091E42;
	--light-text-secondary: #626F86;
	--light-primary: #1D7AFC;
	--light-text-inverse: #FFFFFF;
	
	/* rendered theme */
	--bg: var(--light-bg);
	--border: var(--light-border);
	--surface: var(--light-surface);
	--text-primary: var(--light-text-primary);
	--text-secondary: var(--light-text-secondary);
	--primary: var(--light-primary);
	--text-inverse: var(--light-text-inverse);
}

.theme-fab {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 50px;
    height: 50px;
    background: var(--primary);
    color: var(--text-inverse);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    cursor: pointer;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
    z-index: 9999;
    transition: transform 0.3s;
}
.theme-fab:hover {
    transform: rotate(90deg) scale(1.1);
}

.c-card {
	width: 100%;
	max-width: 400px;
	border: 1px solid var(--border);
	border-radius: 1.6rem;
	padding: 2.2rem;
	background: var(--surface);
	position: fixed;
    bottom: 80px;
    right: 20px;
    z-index: 9998;
    display: none;
	box-shadow: 0px 10px 20px rgba(0,0,0,0.3);
}
.c-card.active { display: block; }
.c-card__title { margin: 0 0 .8rem; padding: 0; line-height: 1.2; font-size: 2rem; color: var(--text-primary); }
.c-card__description { margin: 0; padding: 0; line-height: 150%; font-size: 1.2rem; color: var(--text-secondary); }

.c-button { display: inline-flex; padding: 1rem 1.5rem; background: var(--primary); border-radius: .8rem; cursor: pointer; color: var(--text-inverse); font-weight: 700; user-select: none; border: none; margin-top: 15px; }

.c-theme { position: absolute; top: 1.5rem; right: 1.5rem; width: 3rem; height: 3rem; cursor: pointer; display: inline-block; overflow: hidden; background: transparent; color: var(--text-primary); border: 1px solid transparent; border-radius: 0.8rem; padding: .2rem; }
.c-theme:hover { border-color: var(--border); }
.c-theme__grid { position: relative; width: 2.4rem; transition: all 240ms ease-out; }
.c-theme svg { width: 2.4rem; height: 2.4rem; }

.c-box { display: flex; width: 100%; flex-direction: column; background: var(--bg); color: var(--text-secondary); position: relative; padding: 1rem; border-radius: 1.2rem; border: 1px solid var(--border); user-select: none; cursor: pointer; transition: all 120ms ease-out; }
.c-box:hover { transform: scale(1.03); }
.c-box__title { display: flex; align-items: center; width: 100%; font-size: 12px; }
.c-box__icon { width: 1.6rem; height: 1.6rem; margin-right: 0.4rem; }
.c-box__swatches { display: flex; flex-wrap: wrap; margin-top: .8rem; }
.c-box--active { outline: 3px solid var(--primary); }
.c-box--active:after { content: '✓'; position: absolute; top: -1rem; right: -1rem; height: 2rem; width: 2rem; background: var(--primary); border-radius: 999px; color: var(--text-inverse); display: inline-flex; align-items: center; justify-content: center; font-size: 10px; }

.c-swatch { width: 1.5rem; height: 1.5rem; display: inline-block; border-radius: 999px; border: 1px solid var(--border); margin-right: -.5rem; box-shadow: 0px 1px 1px rgba(0,0,0,0.12); }

.c-theme-grid { display: grid; grid-template-columns: repeat(2, 1fr); grid-gap: 1.2rem; margin: 2rem 0; }
</style>

<div class="theme-fab" id="toggleThemeCard"><i class="ph ph-gear"></i></div>

<div class="c-card" id="themeCard">
	<button class="c-theme" id="themePicker"></button>
	<h1 class="c-card__title">Temas</h1>
	<p class="c-card__description">Selecciona un tema visual para el CRM.</p>
	<div class="c-theme-grid" id="themeGrid"></div>
	<button class="c-button" id="btnCycleTheme">Siguiente Tema</button>
</div>

<script>
    const themes = ['dark', 'sunset', 'sunrise', 'light'];
    
    // Recuperar tema de localStorage
    let currentThemeIndex = 3; // Light por defecto
    const savedTheme = localStorage.getItem('crm_theme');
    if(savedTheme && themes.includes(savedTheme)) {
        currentThemeIndex = themes.indexOf(savedTheme);
    }
    
    const themePicker = document.getElementById('themePicker');
    const themeList = document.getElementById('themeGrid');
    const themeCard = document.getElementById('themeCard');
    
    document.getElementById('toggleThemeCard').addEventListener('click', () => {
        themeCard.classList.toggle('active');
    });

    const changeTheme = (theme) => {
        currentThemeIndex = themes.indexOf(theme);
        localStorage.setItem('crm_theme', theme);
        
        document.documentElement.style.setProperty('--bg', `var(--${theme}-bg)`);
        document.documentElement.style.setProperty('--border', `var(--${theme}-border)`);
        document.documentElement.style.setProperty('--surface', `var(--${theme}-surface)`);
        document.documentElement.style.setProperty('--text-primary', `var(--${theme}-text-primary)`);
        document.documentElement.style.setProperty('--text-secondary', `var(--${theme}-text-secondary)`);
        document.documentElement.style.setProperty('--primary', `var(--${theme}-primary)`);
        document.documentElement.style.setProperty('--text-inverse', `var(--${theme}-text-inverse)`);
        
        const themeGrid = themePicker.querySelector('.c-theme__grid');
        
        if(themeList.querySelector('.c-box--active')) {
            themeList.querySelector('.c-box--active').classList.remove('c-box--active');
        }
        
        themeList.querySelectorAll('.c-box').forEach(item => {
            if(item.dataset.theme === theme) {
                item.classList.add('c-box--active');
            }
        });
        
        if(themeGrid) {
            switch(theme) {
                case 'dark': themeGrid.style.top = '0'; break;
                case 'sunset': themeGrid.style.top = '-2.8rem'; break;
                case 'sunrise': themeGrid.style.top = '-5.4rem'; break;
                case 'light': themeGrid.style.top = '-8rem'; break;
            }
        }
    };

    const darkIcon = `<svg fill="currentColor" viewBox="0 0 24 24"><path d="M10 2c-1.82 0-3.53.5-5 1.35C7.99 5.08 10 8.3 10 12s-2.01 6.92-5 8.65C6.47 21.5 8.18 22 10 22c5.52 0 10-4.48 10-10S15.52 2 10 2z"></path></svg>`;
    const sunsetIcon = `<svg fill="currentColor" viewBox="0 0 24 24"><path d="M20 8.69V4h-4.69L12 .69 8.69 4H4v4.69L.69 12 4 15.31V20h4.69L12 23.31 15.31 20H20v-4.69L23.31 12 20 8.69zM12 18c-.89 0-1.74-.2-2.5-.55C11.56 16.5 13 14.42 13 12s-1.44-4.5-3.5-5.45C10.26 6.2 11.11 6 12 6c3.31 0 6 2.69 6 6s-2.69 6-6 6z"></path></svg>`;
    const sunriseIcon = `<svg fill="currentColor" viewBox="0 0 24 24"><path d="M20 15.31 23.31 12 20 8.69V4h-4.69L12 .69 8.69 4H4v4.69L.69 12 4 15.31V20h4.69L12 23.31 15.31 20H20v-4.69zM12 18V6c3.31 0 6 2.69 6 6s-2.69 6-6 6z"></path></svg>`;
    const lightIcon = `<svg fill="currentColor" viewBox="0 0 24 24"><path d="M20 8.69V4h-4.69L12 .69 8.69 4H4v4.69L.69 12 4 15.31V20h4.69L12 23.31 15.31 20H20v-4.69L23.31 12 20 8.69zM12 18c-3.31 0-6-2.69-6-6s2.69-6 6-6 6 2.69 6 6-2.69 6-6 6zm0-10c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4z"></path></svg>`;

    themePicker.innerHTML = `<div class="c-theme__grid">${darkIcon}${sunsetIcon}${sunriseIcon}${lightIcon}</div>`;

    themePicker.onclick = () => {
        let next = currentThemeIndex + 1;
        if(next > 3) next = 0;
        changeTheme(themes[next]);
    };

    document.getElementById('btnCycleTheme').onclick = () => {
        let next = currentThemeIndex + 1;
        if(next > 3) next = 0;
        changeTheme(themes[next]);
    };

    const capitalized = (word) => word.charAt(0).toUpperCase() + word.slice(1);

    themes.forEach((theme, i) => {
        let box = document.createElement('div');
        box.dataset.theme = theme;
        box.onclick = () => changeTheme(themes[i]);
        box.classList = 'c-box';
        box.style.setProperty('--bg', `var(--${theme}-bg)`);
        box.style.setProperty('--border', `var(--${theme}-border)`);
        box.style.setProperty('--surface', `var(--${theme}-surface)`);
        box.style.setProperty('--text-primary', `var(--${theme}-text-primary)`);
        box.style.setProperty('--text-secondary', `var(--${theme}-text-secondary)`);
        box.style.setProperty('--primary', `var(--${theme}-primary)`);
        box.style.setProperty('--text-inverse', `var(--${theme}-text-inverse)`);
        
        const iconRender = (t) => {
            if(t === 'dark') return darkIcon;
            if(t === 'sunset') return sunsetIcon;
            if(t === 'sunrise') return sunriseIcon;
            return lightIcon;
        };
        
        box.innerHTML = `
            <div class="c-box__title">
                <span class="c-box__icon">${iconRender(theme)}</span>
                <label style="cursor:pointer;">${capitalized(theme)}</label>
            </div>
            <div class="c-box__swatches">
                <span class="c-swatch" style="background: var(--bg)"></span>
                <span class="c-swatch" style="background: var(--border)"></span>
                <span class="c-swatch" style="background: var(--surface)"></span>
                <span class="c-swatch" style="background: var(--text-primary)"></span>
                <span class="c-swatch" style="background: var(--text-secondary)"></span>
                <span class="c-swatch" style="background: var(--primary)"></span>
                <span class="c-swatch" style="background: var(--text-inverse)"></span>
            </div>
        `;
        themeList.appendChild(box);
    });

    // Iniciar con el tema guardado
    changeTheme(themes[currentThemeIndex]);
</script>
