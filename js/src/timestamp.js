export default class Timestamp {
    constructor(totalMs) {
        this.totalMs = totalMs;
    }

    get totalSeconds() {
        return Math.floor(this.totalMs / 1000);
    }

    get totalMinutes() {
        return Math.floor(this.totalSeconds / 60);
    }
    
    get totalHours() {
        return Math.floor(this.totalMinutes / 60);
    }
    
    get milliseconds() {
        return this.totalMs % 1000;
    }
    
    get seconds() {
        return this.totalSeconds % 60;
    }
    
    get minutes() {
        return this.totalMinutes % 60;
    }
    
    get hours() {
        return this.totalHours % 60;
    }

    get pretty() {
      return this.pad(this.totalHours) + ":" + this.pad(this.minutes) + ":" + this.pad(this.seconds) + "." + this.pad(this.milliseconds, "000");
    }
    
    pad(num, pad = "00") {
        let str = "" + num;

        if (str.length < pad.length) {
            return pad.substring(0, pad.length - str.length) + str;
        } else {
            return num;
        }
    }
}
