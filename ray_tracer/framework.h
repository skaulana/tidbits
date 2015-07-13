//
//   FRAMEWORK.H
//
//   Framework classes for our geometric objects - now independent of OpenGL.
//   Uses the SmVector3 class for vector arithmetic.
//

#ifndef _FRAMEWORK_H
#define _FRAMEWORK_H

#include <vector>
#include <cmath>

#include "smVector.h"

using namespace std;

class Primitive;
class Triangle;
class Sphere;
class Transform;
class Object;
class Light;
class Ray;
class RaySource;

//
//   Low-level geometry classes.
//

class Primitive
{
	public:
		int id; Object *o;

		Primitive() : o(NULL), id(-1) {}

		inline Object &owner()            { return *o; }
		inline void setOwner(Object *obj) { o = obj;   }
		inline bool owned()               { return !(o == NULL); }

		virtual SmVector3 normal(SmVector3 &, SmVector3 &) = 0;

		virtual bool isTriangle() { return false; }
};

class Triangle : public Primitive
{
	public:
		int v[3]; SmVector3 abg; // barycentric representation

		int &operator[] (const int &i) { return v[i]; }
		void set(const int &i0, const int &i1, const int &i2) { v[0] = i0; v[1] = i1; v[2] = i2; }

		SmVector3 normal(); // same face normal, regardless of surface point
		SmVector3 normal(SmVector3 &, SmVector3 &);

		bool isTriangle() { return true; }
};

class Sphere : public Primitive
{
	public:
		int v;   // vertex of center
		float r; // radius from center

		SmVector3 normal(SmVector3 &, SmVector3 &); // going out from the center
};

//
//   High-level geometry classes.
//

class Transform
{
	public:
		static const int ROTATE = 1;
		static const int TRANSLATE = 2;
		static const int SCALE = 3;

		Transform(){}; ~Transform(){};
		Transform(const Transform &that);
		Transform &operator=(const Transform &that);

		int type;     // TRANSLATE, ROTATE, or SCALE

		SmVector3 axis, translate, scale; double angle;

		RaySource   &applyTo(RaySource &target);      // transform the image plane
		SmVector3   &applyTo(SmVector3 &target, int); // apply directly to a vector
		SmVector3 &unapplyTo(SmVector3 &target, int); // apply inverse directly
};

class Object
{
	public:
		Object() : bounding_box(NULL) {}
		Object(float, float, float, float, float, float, vector<Transform>); // bounding box ctor
		~Object() { vertices.clear(); triangles.clear(); delete bounding_box; }

		void readObject(char *);

		vector<SmVector3> vertices;
		vector<Triangle> triangles;
		vector<Sphere> spheres;
		vector<Transform> transforms;

		Object *bounding_box;
		inline bool isBounded() { return !(bounding_box == NULL); }

		SmVector3 color;
		float ka, kd, ks, krefl;

		vector<SmVector3> vertexNormals; // a normal for each vertex, for smooth shading
		vector<SmVector3> faceNormals;   // a normal for each face, for flat shading

		int id; bool normalsComputed;

		void computeFaceNormals();       // actually compute those normals

		void applyTransforms(SmVector3 &);        // apply directly to a vector
		void applyInverseTransforms(SmVector3 &); // inverse of the above function

		void untransformRay(Ray &);        // to aid ray intersection
		void transformNormal(SmVector3 &); // to aid light calculation
};

class Light
{
	public:
		static const int POINT = 1;
		static const int DIRECTIONAL = 2;

		int id, type;
		SmVector3 ambient, diffuse, specular; // colors each in RGB
		SmVector3 pos, dir;                   // position or direction in XYZ
};

//
//   Ray classes.
//

class Ray
{
	public:
		Ray();
		Ray(const SmVector3 &p, const SmVector3 &d) : pos(p), dir(d) { normalize(dir); }

		SmVector3 pos; // location in space
		SmVector3 dir; // direction in space

		SmVector3 t(float &param) { return pos+(param*dir); } // trace to a point in space

		// all these return the distance to the intersection point

		float intersectTriangle(Triangle &);
		float intersectTriangle(Triangle &, SmVector3 &); // also grab abg
		float intersectTriangle(SmVector3 &, SmVector3 &, SmVector3 &, SmVector3 &);
		float intersectSphere(Sphere &);
		float intersectSphere(SmVector3 &, float &);
};

class RaySource // replaces the camera
{
	public:
		SmVector3 pos;            // location in space
		SmVector3 ul, ur, ll, lr; // four corners of image plane

		int width, height;        // granularity of the grid

		Ray getPixelRay(const int &, const int &);
		Ray getJitteredPixelRay(const int &, const int &, const int &, const int &, const int &);
};

ostream &operator<<(ostream &, const Ray &);

#endif
