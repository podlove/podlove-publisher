/*! This file contains all code to display regression data.
 * It is expected that the the following two objects are defined globally:
 * reg_data0_ und reg_parm0_.
 *
 * The 1st one, reg_data0_ must be an array of objects. Each element of the
 * array represents one data point of the regression. The datapoint is a JS
 * object with the following members:
 * n: number of the episode,
 * val: number of downloads of the episode,
 * rval: regression value of the episode
 * dev: deviation, i.e. val - rval
 * p: deviation as percentage (-1 <= p <= 1)
 * px: deviation as displayable text (i.e. +13%)
 * tx: text for tooltips.
 *
 * The 2nd object reg_parm0_ contains the regression parameters. It shall have
 * the following members:
 * b0: regression parameter b0 (y = b0 + b1 * x)
 * b1: regression parameter b1
 * r2: "r squared", quality of the regression
 * max: maximum of all val and rval members of reg_data0_
 * ystep: distance of y grid lines
 *
 * @author Bernhard R. Fischer, 4096R/8E24F29D <bf@abenteuerland.at>
 * @date 2020/01/06
 * @version 2.0
 */

//! colors of diagram
const RegCol =
{
   //! axis and grid lines
   AXIS: '#202020',
   //! bars of episodes
   BARS: '#6a5acde0',
   //! negative deviation bars
   NBARS: '#fa8072a0',
   //! positive deviation bars
   PBARS: '#98fb98',
   //! regression line
   REGLINE: '#ffd700b0',
   //! text color
   TEXT: '#202020',
};


/*! Helper function to round floating point number to specific precision.
 * @param num Number to round.
 * @param prec Precision.
 * @return Returns the rounded number.
 */
function round(num, prec)
{
   var m = Math.pow(10, prec);
   return Math.round((num + Number.EPSILON) * m) / m;
}


/*! This is a helper class to render multiline text. The multiline text is
 * represented by an array of strings.
 */
class MultiLine
{
   /*! Constructor.
    * @param ctx Drawing context.
    * @param s Multiline text. The lines are separated by line feeds ('\n').
    */
   constructor(ctx, s)
   {
      //! drawing context
      this.ctx_ = ctx;
      //! array of multiline text
      this.s_ = s.split("\n");
      //! width
      this.w_ = 0;
      //! height
      this.h_ = 0;
      //! line height
      this.hl_ = 0;
   }


   //! calculate text metrics
   calc()
   {
      for (var i = 0; i < this.s_.length; i++)
         this.w_ = Math.max(this.w_, this.ctx_.measureText(this.s_[i]).width);
      // estimate text height
      this.hl_ = this.ctx_.measureText('M').width * 1.5;
      this.h_ = this.hl_ * this.s_.length;
   }


   //! return width
   get width()
   {
      if (!this.w_)
         this.calc();

      return this.w_;
   }


   //! return height
   get height()
   {
      if (!this.h_)
         this.calc();

      return this.h_;
   }


   //! return line height
   get lineHeight()
   {
      if (!this.hl_)
         this.calc();

      return this.hl_;
   }


   //! return number of lines
   get length()
   {
      return this.s_.length;
   }


   //! print text
   fillText(x, y)
   {
      for (var i = 0; i < this.s_.length; i++, y += this.hl_)
         this.ctx_.fillText(this.s_[i], x, y);
   }
}


class Regression
{
   constructor(canvas, rdata, rparm)
   {
      this.border_ = 40;

      this.canvas_ = canvas;
      this.rdata_ = rdata;
      this.rparm_ = rparm;

      this.ctx_ = this.canvas_.getContext('2d');

      this.b0_ = round(this.rparm_.b0, 1);
      this.b1_ = round(this.rparm_.b1, 1);
      this.r2_ = round(this.rparm_.r2, 2);
   }


   scale_params()
   {
      this.sx_ = (this.canvas_.width - 2 * this.border_) / this.rdata_.length;
      this.sy_ = (this.canvas_.height - 2 * this.border_) / this.rparm_.max;
   }


   get canvas()
   {
      return this.canvas_;
   }


   set_tooltip_canvas(c)
   {
      this.tx_canvas_ = c;
      this.tctx_ = this.tx_canvas_.getContext('2d');
   }


   ycap(y)
   {
      if (y < 1000)
         return y;

      var k = round(y / 1000, 1);
      return k + 'k';
   }


   draw()
   {
      if (!this.sx_)
         this.scale_params();

      // diagram border
      this.ctx_.strokeStyle = RegCol.AXIS;
      this.ctx_.beginPath();
      this.ctx_.moveTo(this.border_, this.border_);
      this.ctx_.lineTo(this.border_, this.canvas_.height - this.border_);
      this.ctx_.lineTo(this.canvas_.width - this.border_, this.canvas_.height - this.border_);
      this.ctx_.stroke();
   
      this.ctx_.setLineDash([8, 2]);
      for (var i = this.rparm_.ystep; i <= this.rparm_.max; i += this.rparm_.ystep)
      {
         var y = this.canvas_.height - this.border_ - i * this.sy_;

         this.ctx_.beginPath();
         this.ctx_.moveTo(this.border_, y);
         this.ctx_.lineTo(this.canvas_.width - this.border_, y);
         this.ctx_.stroke();

         var yc = this.ycap(i);
         this.ctx_.fillText(yc, this.border_ - this.ctx_.measureText(yc).width - 5, y);
      }
      this.ctx_.setLineDash([]);

      for (var i = 0; i < this.rdata_.length; i++)
      {
         // calculate coordinates
         this.rdata_[i].x1 = this.border_ + (i + .25) * this.sx_;
         this.rdata_[i].y1 = this.canvas_.height - this.border_;
         this.rdata_[i].w = this.sx_ / 2;
         this.rdata_[i].h = -this.rdata_[i].val * this.sy_;
         this.rdata_[i].x2 = this.rdata_[i].x1 + this.rdata_[i].w;
         this.rdata_[i].y2 = this.rdata_[i].y1 + this.rdata_[i].h;

         // episode bars
         this.ctx_.fillStyle = RegCol.BARS;
         this.ctx_.beginPath();
         this.ctx_.rect(this.rdata_[i].x1, this.rdata_[i].y1, this.rdata_[i].w, this.rdata_[i].h);
         this.ctx_.fill();

         // deviation bars
         this.ctx_.fillStyle = this.rdata_[i].p < 0 ? RegCol.NBARS : RegCol.PBARS;
         this.ctx_.beginPath();
         this.ctx_.rect(this.rdata_[i].x1 + this.rdata_[i].w * .25, this.rdata_[i].y1, this.rdata_[i].w * .5, -this.rdata_[i].dev * this.sy_);
         this.ctx_.fill();
      }

      // draw regression line
      this.ctx_.strokeStyle = RegCol.REGLINE;
      this.ctx_.lineWidth = 5;
      this.ctx_.beginPath();
      this.ctx_.moveTo(this.border_ + .5 * this.sx_, this.canvas_.height - this.border_ - this.rdata_[0].rval * this.sy_);
      this.ctx_.lineTo(this.border_ + (this.rdata_.length - .5) * this.sx_, this.canvas_.height - this.border_ - this.rdata_[this.rdata_.length - 1].rval * this.sy_);
      this.ctx_.stroke();

      // captions
      this.ctx_.fillStyle = RegCol.TEXT;
      for (var i = 0; i < this.rdata_.length; i++)
      {
         this.ctx_.fillText(this.rdata_[i].px, this.rdata_[i].x1 + (this.rdata_[i].w - this.ctx_.measureText(this.rdata_[i].px).width) / 2, this.rdata_[i].y2);
         this.ctx_.fillText(this.rdata_[i].n, this.rdata_[i].x1 + (this.rdata_[i].w - this.ctx_.measureText(this.rdata_[i].n).width) / 2, this.rdata_[i].y1 + 15);
      }

      // regression parameters
      this.ctx_.fillText('y = ' + this.b0_ + ' + ' + this.b1_ + 'x', this.border_ + 5, this.border_ + 10);
      this.ctx_.fillText('r² = ' + this.r2_, this.border_ + 5, this.border_ + 25);
   }


   mouse_move_handler(e)
   {
      // check if there is a tooltip canvas (context)
      if (!this.tctx_)
         return;

      var rect = this.canvas_.getBoundingClientRect();
      var mouseX = e.clientX - rect.left;
      var mouseY = e.clientY - rect.top;
      var i;

      for (i = 0; i < this.rdata_.length; i++)
      {
         if (mouseX >= this.rdata_[i].x1 && mouseY <= this.rdata_[i].y1 && mouseX <= this.rdata_[i].x2 && mouseY >= this.rdata_[i].y2)
         {
            var tc = new MultiLine(this.tctx_, this.rdata_[i].tx);
            this.tx_canvas_.style.left = this.rdata_[i].x1 + 10 + 'px';
            this.tx_canvas_.style.top = this.rdata_[i].y2 + 10 + 'px';
            this.tx_canvas_.width = tc.width + 10;
            this.tx_canvas_.height = tc.height + tc.lineHeight;
            this.tctx_.clearRect(0, 0, this.tx_canvas_.width, this.tx_canvas_.height);
            tc.fillText(5, 15);
            break;
         }
      }

      if (i >= this.rdata_.length)
         this.tx_canvas_.style.left = '-400px';
   }


   resize(e)
   {
      this.canvas_.width = document.getElementById('reg_cv_container').offsetWidth - 10;
      this.canvas_.height = document.getElementById('reg_cv_container').offsetHeight;
      this.scale_params();
      this.draw();
   }
}

var jsr = new Regression(document.getElementById('reg_cv'), reg_data0_, reg_parm0_);
jsr.set_tooltip_canvas(document.getElementById('reg_cv_tip'));
jsr.resize();
jsr.canvas.addEventListener('mousemove', function(e){jsr.mouse_move_handler(e);});
window.addEventListener('resize', function(e){jsr.resize(e);});

