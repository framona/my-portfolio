import React from 'react';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import UserDashboard from './profileScreen';
import EditProfile from './editProfile';
import ChangePassword from './changePassword';

const Stack = createNativeStackNavigator();

export default function ProfileStack({ onLogout }) {
  return (
    <Stack.Navigator screenOptions={{ headerShown: false }}>
      <Stack.Screen name="Dashboard">
        {(props) => <UserDashboard {...props} onLogout={onLogout} />}
      </Stack.Screen>
      <Stack.Screen name="EditProfile" component={EditProfile} />
      <Stack.Screen name="ChangePassword" component={ChangePassword} />
    </Stack.Navigator>
  );
}