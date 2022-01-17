interface Chapter {
  start: number;
  title: string;
  url?: string;
  image?: string;
}

declare module '@podlove/utils/keyboard' {
  export module utils {
    export function keydown(callback: Function): void;
    export function keyup(callback: Function): void;
  }
}

declare module 'podcast-chapter-parser-mp4chaps' {
  export function parse(text: string): Chapter[];
}

declare module 'podcast-chapter-parser-audacity' {
  export function parse(text: string): Chapter[];
}

declare module 'podcast-chapter-parser-hindenburg' {
  export function parser(parser: any): {
    parse(text: string): Chapter[];
  }
}

declare module 'podcast-chapter-parser-psc' {
  export function parser(parser: any): {
    parse(text: string): Chapter[];
  }
}





