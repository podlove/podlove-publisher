const path = require('path')
const fs = require('fs-extra')

const toDelete = (fs.readdirSync(path.resolve('.')) || []).filter(item => item !== 'dist')

toDelete.forEach(file => fs.removeSync(path.resolve(file)))

const toCopy = fs.readdirSync(path.resolve('dist')) || []

toCopy.forEach(file => fs.copySync(path.resolve('dist', file), path.resolve('.', file)))

fs.removeSync(path.resolve('dist'))
