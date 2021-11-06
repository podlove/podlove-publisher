export interface PodloveTranscript {
  speaker: string;
  voice: string;
  items: {
    start: string,
    start_ms: number;
    end: string;
    end_ms: number;
    text: string;
  }[]
}
