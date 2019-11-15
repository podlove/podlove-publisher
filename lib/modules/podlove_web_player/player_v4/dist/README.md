# Podlove Web Player

> Sandboxed Podlove Player with the ability to embed and share a specific episode

## Features

- Encapsulate Player in an iframe
- Provide a global function to bootstrap the embedded player
- Parse the provided configuration (resolving the root config, transcripts and chapters asynchroniously)
- Persist selected tabs and playtime to local storage
- Transform url parameters to player actions
- Forward the player api to the embedding page
- Provide additonal extensions for embedding page integration

## Architecture

![Architecture](architecture.svg)

## Getting Started

### Development

1. Bootstrap the web-player package: `lerna bootstrap --hoist`
2. Run the development mode: `npm run dev`
3. Open your browser on `http://localhost:9000` to get started

### Building

1. Make sure that the dependencies are up to date: `lerna bootstrap --hoist`
2. Run the build step: `npm run build`
