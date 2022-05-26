## Static HTML 

## Requirements

| Prerequisite    | How to check | How to install                   |
| --------------- | ------------ | -------------------------------- |
| Node.js >= 10.0 | `node -v`    | [nodejs.org](http://nodejs.org/) |
| gulp >= 4.0.0   | `gulp -v`    | `npm install -g gulp`            |

---

## Installation

Install Node dependencies (Gulp 4.0.2, Nunjucks and a few others)

`npm install gulp --save-dev`
`npm install nunjucks`
`npm install`

Edit `gulpfile.js` for varibales such as folder names

---

### Build Process

`gulp dev` for development
`gulp build` for production (builds to /dist)