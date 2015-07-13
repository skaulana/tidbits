//-------------------------------------------------------------------
//-------------------------------------------------------------------
//
// Simple Spring Mass System
// -- vector lib
//
// Primary Author: James F. O'Brien (obrienj@cc.gatech.edu)
//
// (C) Copyright James F. O'Brien, 1995
// (C) Copyright Georgia Institute of Technology, 1995
//-------------------------------------------------------------------
//-------------------------------------------------------------------
//
// RCS Revision History
//
// $Log: smVector.C,v $
// Revision 1.2  2004/11/06 02:00:30  adamb
// forced checkin.  still having problems with signing.  This file has code
// (turnded off) to check for bad signs on the mesh, but it can get
// stuck in an infinite loop.
//
// Revision 1.1  2004/09/08 00:01:46  adamb
// initial sources
//
// Revision 1.1.1.1  2004/07/08 08:33:32  adamb
// imported sources
//
// Revision 1.1.1.1  2003/11/05 23:56:30  adamb
// Imported sources
//
// Revision 1.1.1.1  2003/10/27 21:18:23  goktekin
// no message
//
// Revision 1.1.1.1  2003/03/17 10:03:51  adamb
// Initial Revision
//
// Revision 3.0  1999/03/11  22:29:14  obrienj
// Move from O32 ABI to n32
//
// Revision 2.1  1998/02/04  22:12:46  obrienj
// Added SmVector2 routines.
//
// Initial revision
//
//
//-------------------------------------------------------------------
//-------------------------------------------------------------------

#include "smVector.h"

using namespace std;

//-------------------------------------------------------------------


inline static
istream &eatChar(char c,istream &buf) {
  char r;
  buf >> r;
  if (r!=c) {
    buf.clear(buf.rdstate() | ios::failbit);
  }
  return buf;
}

//-------------------------------------------------------------------

istream &operator>>(istream &strm,SmVector3 &v) {
  ios::fmtflags orgFlags = strm.setf(ios::skipws);
  eatChar('[',strm);
  strm >> v[0];
  eatChar(',',strm);
  strm >> v[1];
  eatChar(',',strm);
  strm >> v[2];
  eatChar(']',strm);
  strm.flags(orgFlags);
  return strm;
}
  

ostream &operator<<(ostream &strm,const SmVector3 &v) {
  strm << "[";
  strm << v[0]; strm << ",";
  strm << v[1]; strm << ",";
  strm << v[2]; strm << "]";
  return strm;
}

//-------------------------------------------------------------------


istream &operator>>(istream &strm,SmVector2 &v) {
  ios::fmtflags orgFlags = strm.setf(ios::skipws);
  eatChar('[',strm);
  strm >> v[0];
  eatChar(',',strm);
  strm >> v[1];
  eatChar(']',strm);
  strm.flags(orgFlags);
  return strm;
}
  

ostream &operator<<(ostream &strm,const SmVector2 &v) {
  strm << "[";
  strm << v[0]; strm << ",";
  strm << v[1]; strm << "]";
  return strm;
}

//-------------------------------------------------------------------


