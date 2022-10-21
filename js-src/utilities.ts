// Utility code


export class Util {


    // Force a window scrolling event, to trigger handlers
    static fakeScroll() {
        var x = window.scrollX;
        var y = window.scrollY;
        window.scrollTo(x, y+1);
        window.scrollTo(x, y);
    }


}