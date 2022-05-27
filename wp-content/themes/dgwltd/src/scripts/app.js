//Import ES6 dependencies - per ES6 imports, we can omit the `.js` at the end.
//import LanguagePreference from './modules/language';

;(function () {

    'use strict';

    //Constants
    const desktopWidth = 992;

    //Open links with rel='external' in new window/tab
    const externalLinks = () =>{  

        //If rel is external then open the window in a new tab
        //Get all links with rel="external"
        const externalLinks = document.querySelectorAll('a[rel="external"]');
        //Loop through links and add click event
        for (let i = 0; i < externalLinks.length; i++) {
            externalLinks[i].addEventListener('click', (e) => {
                //Prevent default action
                e.preventDefault();
                //Open new tab
                window.open(externalLinks[i].href);
            });
        };

    };

    //Smooth scroll function
    const smoothScroll = () => {

        const links = document.querySelectorAll('.scroll[href^="#"]'); // console.log(links);

        for (let i = 0; i < links.length; i++) {
          //On click open in new window/tab
          links[i].addEventListener('click', function (e) {
              
            e.preventDefault();
            
            //If ID is just #, then scroll to top
            if (this.getAttribute('href') !== '#') {
                const id = this.getAttribute('href');
                //console.log(id); // smooth scroll to element ID that matches id

                document.querySelector(id).scrollIntoView({
                behavior: 'smooth'
                });
            }
                
            
          });
        }
    };

    //Vanilla nav toggle button
    const toggleNav = (button, elem, masthead)=>{

        //https://piccalil.li/tutorial/build-a-light-and-global-state-system

        //Set up the vars
        const toggleButton = document.querySelector(button);
        const menu = document.querySelector(elem);
        const header = document.querySelector(masthead);

        window.subscribers = [];
        
        const defaultState = {
            status: 'closed',
            enabled: false,
        };

        const state = new Proxy(defaultState, {
            set(state, key, value) {
                const oldState = {...state};

                state[key] = value;

                window.subscribers.forEach(callback => callback(state, oldState));

                return state;
            }
        });

        //If window resized lets watch for when we go bigger than a tablet and switch from the burger menu to a full menu
        const observer = new ResizeObserver((observedItems) => {
            const { contentRect } = observedItems[0];
            // console.log(contentRect);
            // console.log(observedItems[0]);
            if (contentRect.width <= desktopWidth) {
                state.enabled = true;
                observedItems[0].target.setAttribute('enabled', state.enabled);
              } else {
                state.enabled = false;
                observedItems[0].target.setAttribute('enabled', state.enabled);
            }
            
        });
    
        //Watch the header element 
        observer.observe(header);

        //Now an event listener for the burger menu button
        toggleButton.addEventListener('click', function(event) {

            // The JSON.parse function helps us convert the attribute from a string to a real boolean (true/false).
            const open = JSON.parse(toggleButton.getAttribute('aria-expanded'));

            //Switch the state via aria-expanded and set a data attribute status="open" which we can access with CSS
            state.status = open ? 'closed' : 'open';
            toggleButton.setAttribute('aria-expanded', !open);

            /*
            Toggle the menu state:
            Make sure this is not the <nav> as it’s undiscoverable when hidden
            The <nav> should be the surrounding container for the toggled state
            */
            menu.setAttribute('status', state.status);

            //Add an additional class to the header just incase we want to do something with it in it's opened state
            header.classList.toggle('masthead-is-open');
            //Add class to document body
            document.body.classList.toggle('nav-open');

            //Menu height for offset
            //console.log(menu.offsetHeight);
            let root = document.documentElement; //Remove padding
            root.style.setProperty('--submenu-offset', menu.offsetHeight + header.offsetHeight + 'px');
            

        });

        //Close menu if user hits the escape key
        window.addEventListener('keydown', function(event) {

            if (!event.key.includes('Escape')) { return; }
            //Set aria state and our data attribute
            toggleButton.setAttribute('aria-expanded', 'false');
            //Remove the header class
            header.classList.toggle('masthead-is-open');
            document.body.classList.toggle('nav-open');

            state.status = 'closed';
            menu.setAttribute('status', state.status);

            //And remove the class if set
            if (header) {
                header.classList.remove('masthead-is-open');
                document.body.classList.remove('nav-open');
            }
            
        });
        

    };

    const subMenu = (elem, masthead)=>{  
        
        const menu = document.querySelector(elem);
        const header = document.querySelector(masthead);
        
        //If no menu bail
        if(!menu) { return; }

        const submenus = menu.querySelectorAll('.menu-item-has-children');

        //console.log(submenus);

        //If window resized lets watch for when we go bigger than a tablet and switch from the burger menu to a full menu
        const observer = new ResizeObserver((observedItems) => {
        const { contentRect } = observedItems[0];

            Array.prototype.forEach.call(submenus, function(el, i){

                // The JSON.parse function helps us convert the attribute from a string to a real boolean (true/false).
                const open = JSON.parse(el.querySelector('a').getAttribute('aria-expanded'));

                if (contentRect.width >= desktopWidth) {

                    el.addEventListener("mouseenter", function(event){
                        // console.log(el);

                        //Find all open menus on the page
                        const openMenus = document.querySelectorAll('.menu-open');

                        // Close all other menus apart from the one we are on
                        Array.prototype.forEach.call(openMenus, function(nav, i){
                            if (nav !== this) {
                                nav.classList.remove('menu-open');
                                nav.querySelector('a').setAttribute('aria-expanded', 'false');
                            }
                        });

                        //document.body.classList.add('masthead-expanded');   
                        el.querySelector('a').setAttribute('aria-expanded', !open);
                        el.classList.toggle('menu-open');
                        document.body.classList.add('masthead-expanded');
                        

                    });   

                    el.addEventListener("mouseleave", function(event){
                        
                        el.querySelector('a').setAttribute('aria-expanded', 'false');
                        el.classList.remove('menu-open');
                        document.body.classList.remove('masthead-expanded');     

                    });

                    //Close menu if user hits the escape key
                    window.addEventListener('keydown', function(event) {

                        if (!event.key.includes('Escape')) { return; }
                        document.body.classList.remove('masthead-expanded'); 
                        el.querySelector('a').setAttribute('aria-expanded', 'falqse');
                        el.classList.remove('menu-open');

                    });
                    
                }
            });
            
        });

        // a11y menu
        // Use button as toggle
        Array.prototype.forEach.call(submenus, function(el, i){

            const activatingA = el.querySelector('a');
            const btn = '<button class="button-show-subnav"><span>show sub menu for "' + activatingA.text + '"</span></button>';
            activatingA.insertAdjacentHTML('afterend', btn);
        
            el.querySelector('button').addEventListener("click",  function(event){

                //If nav is current page remove the classes as it's open by default
                Array.prototype.forEach.call(submenus, function(el, i){
                    if(el.classList.contains('current-menu-item') || el.classList.contains('current-menu-ancestor')) {
                        el.classList.remove('current-menu-item');
                        el.classList.remove('current-menu-ancestor');
                    }
                });
                
                //console.log(el);

                //Toggle menu open class
                this.parentNode.classList.toggle('menu-open');

                // Find all open menus on the page
                const openMenus = document.querySelectorAll('.menu-open');
                const currentMenu = this.parentNode;

                // Close all other menus apart from the one we are on
                Array.prototype.forEach.call(openMenus, function(nav, i) {

                    if (nav !== currentMenu) {
                        nav.classList.remove('menu-open');
                        nav.querySelector('a').setAttribute('aria-expanded', 'false');
                    }
                });

                //Set aria attributes based on whether menu is open or closed
                if (this.parentNode.classList.contains('menu-open')) {
                    //Close all other menus
                    this.parentNode.querySelector('a').setAttribute('aria-expanded', "true");
                    this.parentNode.querySelector('button').setAttribute('aria-expanded', "true");
                } else {
                    this.parentNode.querySelector('a').setAttribute('aria-expanded', "false");
                    this.parentNode.querySelector('button').setAttribute('aria-expanded', "false");
                }
                event.preventDefault();
                return false;
            });

        });

        //Watch the header element 
        observer.observe(header);
        
    };

    const cardClick = (elem)=>{  

        const cardLinks = document.querySelectorAll(elem);

        if (!cardLinks) return;

        Array.prototype.forEach.call(cardLinks, function(card, i){

            card.addEventListener("click", handleClick);

            // Click handler but only if text is not selected
            function handleClick(event) {
                const isTextSelected = window.getSelection().toString();
                if (!isTextSelected) {
                    window.location = card.dataset.url;
                }
            }

        });   
        
    };

    //Init
    document.addEventListener("DOMContentLoaded", function() {
        externalLinks();
        smoothScroll();
        toggleNav('#nav-toggle', '#nav-primary', '#masthead');
        subMenu('#nav-primary', '#masthead');
        cardClick('.dgwltd-card');
     });
    
})();