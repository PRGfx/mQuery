<?php
echo'
Void _slideExec(CMlControl e)
{
	declare Vec2 _slideAniSWidth for e;
	declare Boolean _slideAniAccel for e;
	declare Vec2 _slideAniTo for e;
	declare Integer _slideAniSteps for e;
	declare Integer _slideAniState for e;
	if(_slideAniState < _slideAniSteps)
	{
		declare Real sx = _slideAniSWidth[0];
		declare Real sy = _slideAniSWidth[1];
		if(_slideAniAccel)
		{
			declare Real mod = (MathLib::PI() / 2.) * MathLib::Sin(MathLib::PI()
			*(MathLib::ToReal(_slideAniState)/MathLib::ToReal(_slideAniSteps)));
			sx *= mod;
			sy *= mod;
		}
		e.PosnX += sx;
		e.PosnY += sy;
		_slideAniState += 1;
		setTimeout("_slideExec", 1, e);
	} else {
		e.PosnX = _slideAniTo[0];
		e.PosnY = _slideAniTo[1];
	}
}

Void Slide(CMlControl e, Vec2 to, Integer steps, Boolean accel)
{
	declare Real dx = to[0] - e.PosnX;
	declare Real dy = to[1] - e.PosnY;
	declare Real sx = dx / steps;
	declare Real sy = dy / steps;
	declare Vec2 _slideAniSWidth for e;
		_slideAniSWidth = <sx, sy>;
	declare Boolean _slideAniAccel for e;
		_slideAniAccel = accel;
	declare Vec2 _slideAniTo for e;
		_slideAniTo = to;
	declare Integer _slideAniSteps for e;
		_slideAniSteps = steps;
	declare Integer _slideAniState for e;
		_slideAniState = 0;
	setTimeout("_slideExec", 0, e);
}
';