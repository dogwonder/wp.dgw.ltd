const fetch = require("node-fetch");
const path = require("path");
const config = require("./config.json");
const flatCache = require('flat-cache');
const CACHE_KEY = 'menusFooter';
const CACHE_FOLDER = path.resolve("./.cache");
const CACHE_FILE = "menusFooter.json";

async function requestMenusFooter() {

    if (typeof config === 'undefined' || typeof config.graphqlUrl === 'undefined') {
        throw new Error("You must define graphqlUrl in config.json in your _data directory");
    }

    // Check the cache first
    const cache = flatCache.load(CACHE_FILE, CACHE_FOLDER);
    const cachedItems = cache.getKey(CACHE_KEY);

    // console.log(config.useCache);

    if ( config.useCache && cachedItems ) {
        console.log("Menus cache found, using that instead of the GraphQL API. Remove " + CACHE_FOLDER + "/" + CACHE_KEY + " to force a reload");
        return cachedItems;
    }

    let menusFooter = [];
    try {
        const data = await fetch(config.graphqlUrl, {

            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json",
            },
            body: JSON.stringify({
                query: `{
                    menu(id: "Footer Menu", idType: NAME) {
                        count
                        id
                        databaseId
                        name
                        slug
                        menuItems {
                            nodes {
                            id
                            databaseId
                            title
                            url
                            cssClasses
                            description
                            label
                            linkRelationship
                            target
                            parentId
                            }
                        }
                        }
                    }`
            })
        });

        const response = await data.json();

        // console.log(response.data.menu.menuItems.nodes);
        // console.log(response.data.menus.nodes);

        if ( response.errors ) {
            let errors = response.errors;
            errors.map((error) => {
                console.log(error.message);
            });
            throw new Error("Aborting due to error from GraphQL query");
        }

        menusFooter = menusFooter.concat(response.data.menu.menuItems.nodes);
        // menus = menus.concat(response.data.menus.nodes);
        
    } catch ( error ) {
        throw new Error(error);
    }

    // Format posts for returning
    const menusFormatted = menusFooter.map((item) => {
        return {
            id: item.id,
            label: item.label,
            url: item.url,
            classes: item.cssClasses
        };
    });

    if ( menusFormatted.length ) {
        cache.setKey(CACHE_KEY, menusFormatted);
        cache.save();
    }
    return menusFormatted;

}

module.exports = requestMenusFooter;