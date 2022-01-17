export interface PodloveTranscript {
  voice: string;
  start: string,
  start_ms: number;
  end: string;
  end_ms: number;
  text: string;
}

export interface PodloveTranscriptVoice {
  voice: string,
  contributor: string
}
