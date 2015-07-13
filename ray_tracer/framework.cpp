//
//   FRAMEWORK.CPP
//
//   Framework classes for our geometric objects - now independent of OpenGL.
//   Uses the SmVector3 class for vector arithmetic.
//

#ifndef _FRAMEWORK_CPP
#define _FRAMEWORK_CPP

#include <iostream>
#include <fstream>
#include <vector>
#include <cmath>

#include "framework.h"

using namespace std;

extern bool DEBUG, BOUNDING_BOX;
extern bool mostlyEqual(float, float);
extern float MAX_DIST;

//
//   Return a surface normal (either for the face itself, or interpolated to a point).
//

SmVector3 Triangle::normal()
{
	if (!owned())
	{
		cerr << "Triangle has no owner!" << endl;
		return 0.0f;
	}
	else if (owner().normalsComputed)
	{
		return owner().faceNormals[id];
	}
	else
	{
		// cross product, (c - b) x (a - b)

		return cross(owner().vertices[v[2]] - owner().vertices[v[1]], owner().vertices[v[0]] - owner().vertices[v[1]]);
	}
}

SmVector3 Triangle::normal(SmVector3 &surface, SmVector3 &abg)
{
	if (!owned())
	{
		cerr << "Triangle has no owner!" << endl;
		return 0.0f;
	}
	else if (!(owner().normalsComputed))
	{
		cerr << "Triangle's owner has no vertex normals!" << endl;
		return normal();
	}
	else
	{
		// barycentric interpolation, alpha * na + beta * nb + gamma * nc

		return abg[0]*owner().vertexNormals[v[0]]+abg[1]*owner().vertexNormals[v[1]]+abg[2]*owner().vertexNormals[v[2]];
	}
}

//
//   Return a surface normal that depends on the nearest surface point.
//

SmVector3 Sphere::normal(SmVector3 &surface, SmVector3 &abg)
{
	if (!owned())
	{
		cerr << "Sphere has no owner!" << endl;
		return 0.0f;
	}
	else
	{
		// from sphere center, to the point of intersection

		return surface - (owner().vertices[v]);
	}
}

//
//   Transform copy constructor.
//

Transform::Transform(const Transform &that)
{
	type = that.type;
	axis = that.axis;
	translate = that.translate;
	scale = that.scale;
	angle = that.angle;
}

//
//   Transform assignment operator.
//

Transform &Transform::operator=(const Transform &that)
{
	type = that.type;
	axis = that.axis;
	translate = that.translate;
	scale = that.scale;
	angle = that.angle;
	return *this;
}

//
//   Fake the usual matrix operations using just vectors. Added parameter to apply only certain transforms.
//

SmVector3 &Transform::applyTo(SmVector3 &target, int apply = 0)
{
	SmVector3 u, v, w;
	float ct, st, ct1m;

	if (!(apply == 0 || apply == type)) return target;

	switch (type)
	{
		case Transform::TRANSLATE:

			target += translate;
			break;

		case Transform::ROTATE:

			ct = cos(angle); st = sin(angle); ct1m = 1.0f - ct;

			u.set(axis[0]*axis[0]*ct1m+ct,         axis[0]*axis[1]*ct1m-axis[2]*st, axis[0]*axis[2]*ct1m+axis[1]*st);
			v.set(axis[1]*axis[0]*ct1m+axis[2]*st, axis[1]*axis[1]*ct1m+ct,         axis[1]*axis[2]*ct1m-axis[0]*st);
			w.set(axis[2]*axis[0]*ct1m-axis[1]*st, axis[2]*axis[1]*ct1m+axis[0]*st, axis[2]*axis[2]*ct1m+ct        );

			target.set(dot(u,target), dot(v,target), dot(w,target));
			break;

		case Transform::SCALE:

			target *= scale;
			break;

		default: break;
	}

	return target;
}

SmVector3 &Transform::unapplyTo(SmVector3 &target, int apply = 0)
{
	SmVector3 u, v, w;
	float ct, st, ct1m;

	if (!(apply == 0 || apply == type)) return target;

	switch (type)
	{
		case Transform::TRANSLATE:

			target -= translate;
			break;

		case Transform::ROTATE:

			ct = cos(-angle); st = sin(-angle); ct1m = 1.0f - ct;

			u.set(axis[0]*axis[0]*ct1m+ct,         axis[0]*axis[1]*ct1m-axis[2]*st, axis[0]*axis[2]*ct1m+axis[1]*st);
			v.set(axis[1]*axis[0]*ct1m+axis[2]*st, axis[1]*axis[1]*ct1m+ct,         axis[1]*axis[2]*ct1m-axis[0]*st);
			w.set(axis[2]*axis[0]*ct1m-axis[1]*st, axis[2]*axis[1]*ct1m+axis[0]*st, axis[2]*axis[2]*ct1m+ct        );

			target.set(dot(u,target), dot(v,target), dot(w,target));			
			break;

		case Transform::SCALE:

			target /= scale;
			break;

		default: break;
	}

	return target;
}

RaySource &Transform::applyTo(RaySource &target)
{
	applyTo(target.pos, Transform::TRANSLATE);
	applyTo(target.ul, 0); applyTo(target.ur, 0);
	applyTo(target.ll, 0); applyTo(target.lr, 0);
}

//
//   Object cube constructor. Accepts 6 parameters for each of the planes.
//   Build order looks something like:
//
//        1------2
//       /|     /|   - Build these 8 vertices
//      3------4 |   - Build two triangles per face:
//      | |    | |
//      | 5----|-6     - 132 234   - 675 876
//      |/     |/      - 374 478   - 251 652
//      7------8       - 482 286   - 173 571
//

Object::Object(float xmin, float xmax, float ymin, float ymax, float zmin, float zmax, vector<Transform> ts)
{
	bounding_box = NULL; normalsComputed = false; transforms = ts;

	ka = 1.0f; kd = 1.0f; ks = 1.0f; krefl = 0.0f; color.set(0.0f, 1.0f, 0.0f);

	SmVector3 v; Triangle t; int tcount = 0;

	v.set(xmin, ymax, zmin); vertices.push_back(v); v.set(xmax, ymax, zmin); vertices.push_back(v);
	v.set(xmin, ymax, zmax); vertices.push_back(v); v.set(xmax, ymax, zmax); vertices.push_back(v);
	v.set(xmin, ymin, zmin); vertices.push_back(v); v.set(xmax, ymin, zmin); vertices.push_back(v);
	v.set(xmin, ymin, zmax); vertices.push_back(v); v.set(xmax, ymin, zmax); vertices.push_back(v);

	t.set(1-1, 3-1, 2-1); t.setOwner(this); t.id = tcount++; triangles.push_back(t);
	t.set(2-1, 3-1, 4-1); t.setOwner(this); t.id = tcount++; triangles.push_back(t);
	t.set(3-1, 7-1, 4-1); t.setOwner(this); t.id = tcount++; triangles.push_back(t);
	t.set(4-1, 7-1, 8-1); t.setOwner(this); t.id = tcount++; triangles.push_back(t);
	t.set(4-1, 8-1, 2-1); t.setOwner(this); t.id = tcount++; triangles.push_back(t);
	t.set(2-1, 8-1, 6-1); t.setOwner(this); t.id = tcount++; triangles.push_back(t);
	t.set(6-1, 7-1, 5-1); t.setOwner(this); t.id = tcount++; triangles.push_back(t);
	t.set(8-1, 7-1, 6-1); t.setOwner(this); t.id = tcount++; triangles.push_back(t);
	t.set(2-1, 5-1, 1-1); t.setOwner(this); t.id = tcount++; triangles.push_back(t);
	t.set(6-1, 5-1, 2-1); t.setOwner(this); t.id = tcount++; triangles.push_back(t);
	t.set(1-1, 7-1, 3-1); t.setOwner(this); t.id = tcount++; triangles.push_back(t);
	t.set(5-1, 7-1, 1-1); t.setOwner(this); t.id = tcount++; triangles.push_back(t);
}

//
//   Object input file parser. Grabs vertices, faces (triangles) and spheres.
//

void Object::readObject(char *fname)
{
	ifstream in(fname, ios::in);
	char c;	SmVector3 pt; Triangle t; Sphere s;

	if (!in.good())
	{
		cerr << "Unable to open object \"" << fname << "\" for input." << endl;
		abort();
	}

	int tcount = 0, scount = 0;
	float xmin = 0.0f, xmax = 0.0f, ymin = 0.0f, ymax = 0.0f, zmin = 0.0f, zmax = 0.0f;

	while (in.good())
	{
		in >> c;
		if (!in.good()) break;
		if (c == 'v')
		{
			in >> pt[0] >> pt[1] >> pt[2];
			vertices.push_back(pt);
		}
		else if (c == 'f')
		{
			in >> t[0] >> t[1] >> t[2];

			t[0]-=1; t[1]-=1; t[2]-=1; // reindex starting at 0
			t.id = tcount++; t.setOwner(this);

			triangles.push_back(t);

			if (BOUNDING_BOX) for(int i = 0; i < 2; i++)
			{
				SmVector3 &v = vertices[t[i]];

				if (v[0] < xmin) xmin = v[0]; if (v[0] > xmax) xmax = v[0];
				if (v[1] < ymin) ymin = v[1]; if (v[1] > ymax) ymax = v[1];
				if (v[2] < zmin) zmin = v[2]; if (v[2] > zmax) zmax = v[2];
			}
		}
		else if (c == 's')
		{
			in >> s.v >> s.r;

			s.v-=1; // reindex starting at 0
			s.id = scount++; s.setOwner(this);

			spheres.push_back(s);
		}
	}

	if (BOUNDING_BOX && (xmin != xmax) && (ymin != ymax) && (zmin != zmax))
	{
		bounding_box = new Object(xmin, xmax, ymin, ymax, zmin, zmax, transforms);
	}
}

void Object::computeFaceNormals()
{
	SmVector3 v;

	//
	//   Compute normals for each face of the object. Use these for flat shading.
	//

	for(int i = 0; i < triangles.size(); i++)
	{
		v = triangles[i].normal(); normalize(v);
		faceNormals.push_back(v);
	}

	//
	//   Computer normals for each vertex of the object. Use these for smooth shading.
	//   Requires the above calculations to take an average.
	//

	for(int i = 0; i < vertices.size(); i++)
	{
		vector<SmVector3> sharedFaceNormals;

		for(int j = 0; j < triangles.size(); j++)
		{
			Triangle &t = triangles[j];

			if (i == t[0] || i == t[1] || i == t[2]) sharedFaceNormals.push_back(faceNormals[j]);
		}

		v = 0.0f;

		//
		//   Short circuit the loop if no triangles use this vertex (could belong to a sphere instead).
		//

		if (sharedFaceNormals.size() == 0)
		{
			vertexNormals.push_back(v);
		}
		else
		{
			SmVector3 n0 = sharedFaceNormals[0];

			//
			//   Use the dot product to ensure all normals are pointing in a consistent direction.
			//

			for(int j = 0; j < sharedFaceNormals.size(); j++)
			{
				if (dot(sharedFaceNormals[j], n0) < 0.0f) v -= sharedFaceNormals[j];
				else v += sharedFaceNormals[j];
			}

			normalize(v); vertexNormals.push_back(v);
		}

		sharedFaceNormals.clear();
	}

	normalsComputed = true;
}

void Object::applyTransforms(SmVector3 &target)
{
	for(int i = transforms.size() - 1; i >= 0; i--)	transforms[i].applyTo(target);
}

void Object::applyInverseTransforms(SmVector3 &target)
{
	for(int i = transforms.size() - 1; i >= 0; i--) transforms[i].unapplyTo(target);
}

//
//   Apply object transformations to allow for correct intersect and lighting calculations.
//

void Object::untransformRay(Ray &target) // E_std = S^-1*R^-1*T^-1*E, D_std = S^-1*R^-1*D
{
	for(int i = 0; i < transforms.size(); i++)
	{
		transforms[i].unapplyTo(target.pos);
		transforms[i].unapplyTo(target.dir, Transform::ROTATE); // don't translate the direction
		transforms[i].unapplyTo(target.dir, Transform::SCALE);
	}
}

void Object::transformNormal(SmVector3 &target) // N = R*S^-1*N_std
{
	for(int i = transforms.size() - 1; i >= 0; i--)
	{
		transforms[i].unapplyTo(target, Transform::SCALE);
		transforms[i].applyTo(target, Transform::ROTATE);
	}
}

//
//   Intersection tests for a ray against surfaces. Returns the distance parameter to give you a way
//   to calculate the intersection point. -1 is returned if there is no intersection in MAX_DIST.
//

float Ray::intersectTriangle(Triangle &t)
{
	SmVector3 v = 0.0f;
	return intersectTriangle(t, v);
}

float Ray::intersectTriangle(Triangle &t, SmVector3 &abg)
{
	if (!t.owned())
	{
		cerr << "Triangle has no owner!" << endl;
		return -1.0f;
	}
	else return intersectTriangle(t.owner().vertices[t[0]], t.owner().vertices[t[1]], t.owner().vertices[t[2]], abg);
}

//
//   A side effect of this calculation is to produce barycentric coordinates (alpha, beta, gamma).
//

float Ray::intersectTriangle(SmVector3 &ta, SmVector3 &tb, SmVector3 &tc, SmVector3 &abg)
{
	float a = ta[0] - tb[0], b = ta[1] - tb[1], c = ta[2] - tb[2];
	float d = ta[0] - tc[0], e = ta[1] - tc[1], f = ta[2] - tc[2];
	float g = dir[0], h = dir[1], i = dir[2];
	float j = ta[0] - pos[0], k = ta[1] - pos[1], l = ta[2] - pos[2];

	float eihf = e*i - h*f, gfdi = g*f - d*i, dheg = d*h - e*g;
	float akjb = a*k - j*b, jcal = j*c - a*l, blkc = b*l - k*c;

	float m = a*eihf + b*gfdi + c*dheg;

	float t = -(f*akjb + e*jcal + d*blkc) / m;
	if (t < 0 || t > MAX_DIST) return -1.0f;

	float gamma = (i*akjb + h*jcal + g*blkc) / m;
	if (gamma < 0 || gamma > 1) return -1.0f;

	float beta = (j*eihf + k*gfdi + l*dheg) / m;
	if (beta < 0 || beta > 1-gamma) return -1.0f;

	abg.set(1-beta-gamma, beta, gamma);
	return t;
}

float Ray::intersectSphere(Sphere &s)
{
	if (!s.owned())
	{
		cerr << "Sphere has no owner!" << endl;
		return -1.0f;
	}
	else return intersectSphere(s.owner().vertices[s.v], s.r);
}

float Ray::intersectSphere(SmVector3 &center, float &radius)
{
	SmVector3 ec = pos-center; float dd = dot(dir, dir), dec = dot(dir, ec);

	float discriminant = dec*dec - dd*(dot(ec, ec) - radius*radius);
	if (discriminant < 0) return -1.0f; float root = sqrt(discriminant);

	float t1 = (-dec + root) / dd, t2 = (-dec - root) / dd;

	if (t1 < 0 || t1 > MAX_DIST)
	{
		if (t2 < 0 || t2 > MAX_DIST) return -1.0f;
		else return t2;
	}
	else
	{
		if (t2 < 0 || t2 > MAX_DIST) return t1;
		else return t1 < t2 ? t1 : t2;
	}
}

//
//   Return a ray in the appropriate direction.
//

Ray RaySource::getPixelRay(const int &x, const int &y)
{
	float u = float(x) / float(width - 1), v = float(y) / float(height - 1);
	SmVector3 pixel = u*(v*lr + (1-v)*ur) + (1-u)*(v*ll + (1-v)*ul);

	SmVector3 &dir = pixel; dir -= pos; normalize(dir);
	return Ray(pos, dir);
}

//
//   Return the ijth jittered ray in an n x n grid.
//

Ray RaySource::getJitteredPixelRay(const int &x, const int &y, const int &i, const int &j, const int &n)
{
	float u  = float(x)   / float(width - 1), v  = float(y)   / float(height - 1);
	float u1 = float(x+1) / float(width - 1), v1 = float(y+1) / float(height - 1);

	SmVector3 tl =  u*( v*lr +  (1-v)*ur) +  (1-u)*( v*ll +  (1-v)*ul);
	SmVector3 br = u1*(v1*lr + (1-v1)*ur) + (1-u1)*(v1*ll + (1-v1)*ul);

	float invn = float(1.0f / float(n));

	float perturb_x = float(rand() % int(100.0f * invn)) / 100.0f;
	float perturb_y = float(rand() % int(100.0f * invn)) / 100.0f;

	tl[0] += (float(invn * float(i)) + perturb_x) * (br[0] - tl[0]);
	tl[1] += (float(invn * float(j)) + perturb_y) * (br[1] - tl[1]);

	SmVector3 &dir = tl; dir -= pos; normalize(dir);
	return Ray(pos, dir);
}

//
//   Stream insertion operators.
//

ostream &operator<<(ostream &os, const Ray &r)
{
	return os << r.pos << " + t" << r.dir;
}

#endif
